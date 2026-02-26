package solver

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"log"
	"math/rand"
	"net/http"
	"sort"
	"strings"
	"time"

	"scheduler/internal/db"
	"scheduler/pkg/types"
)

type Scheduler struct {
	db          *db.PostgresDB
	cpsatURL    string
	httpClient  *http.Client
}

func NewScheduler(database *db.PostgresDB, cpsatURL string) *Scheduler {
	return &Scheduler{
		db:       database,
		cpsatURL: cpsatURL,
		httpClient: &http.Client{
			Timeout: 10 * time.Minute,
		},
	}
}

func (s *Scheduler) HandleGenerate(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req types.ScheduleRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	timeout := int(req.TimeoutSeconds)
	if timeout == 0 {
		timeout = 420
	}

	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(timeout)*time.Second)
	defer cancel()

	result, err := s.solve(ctx, &req)
	if err != nil {
		log.Printf("Solve error: %v", err)
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(result)
}

func (s *Scheduler) solve(ctx context.Context, req *types.ScheduleRequest) (*types.ScheduleResult, error) {
	startTime := time.Now()

	input, err := s.db.GetScheduleInput(ctx, req.TenantID, req.CalendarID, req.ScheduleID, req.Scope)
	if err != nil {
		return nil, fmt.Errorf("failed to load input: %w", err)
	}

	if len(input.Activities) == 0 {
		return &types.ScheduleResult{
			Status: types.ResultStatus_INFEASIBLE,
		}, nil
	}

	// Route to appropriate algorithm
	if req.Algorithm == "cpsat" && s.cpsatURL != "" {
		return s.solveCPSAT(ctx, input, req, startTime)
	}

	// Default: greedy
	assignments, violations := s.optimize(input, req)

	solveTimeMs := time.Since(startTime).Milliseconds()

	if err := s.saveResults(ctx, input.TenantID, req.ScheduleID, assignments, violations); err != nil {
		log.Printf("Failed to save results: %v", err)
	}

	return &types.ScheduleResult{
		Status:          types.ResultStatus_FEASIBLE,
		AssignmentIDs:   extractIDs(assignments),
		Violations:      violations,
		TotalViolations: int32(len(violations)),
		SolveTimeMs:     solveTimeMs,
	}, nil
}

// solveCPSAT proxies the solving to the Python CP-SAT microservice
func (s *Scheduler) solveCPSAT(ctx context.Context, input *types.ScheduleInput, req *types.ScheduleRequest, startTime time.Time) (*types.ScheduleResult, error) {
	// Build request payload for the Python service
	payload := map[string]interface{}{
		"activities":       input.Activities,
		"time_slots":       input.TimeSlots,
		"rooms":            input.Rooms,
		"unavailabilities": input.Unavailabilities,
		"preferences":      input.Preferences,
		"preference_rules": input.PreferenceRules,
		"weights":          req.Weights,
		"timeout_seconds":  req.TimeoutSeconds,
	}

	// Get locked assignments
	lockedAssignments := []map[string]interface{}{}
	// TODO: load locked from DB if needed
	payload["locked_assignments"] = lockedAssignments

	jsonPayload, err := json.Marshal(payload)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal cpsat request: %w", err)
	}

	url := s.cpsatURL + "/api/v1/solve"
	log.Printf("Calling CP-SAT solver at %s with %d activities", url, len(input.Activities))

	httpReq, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(jsonPayload))
	if err != nil {
		return nil, fmt.Errorf("failed to create cpsat request: %w", err)
	}
	httpReq.Header.Set("Content-Type", "application/json")

	resp, err := s.httpClient.Do(httpReq)
	if err != nil {
		return nil, fmt.Errorf("failed to call cpsat solver: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		bodyBytes := make([]byte, 1024)
		n, _ := resp.Body.Read(bodyBytes)
		return nil, fmt.Errorf("cpsat solver returned status %d: %s", resp.StatusCode, string(bodyBytes[:n]))
	}

	var cpsatResult struct {
		Status     string `json:"status"`
		Assignments []struct {
			ActivityID int64  `json:"activity_id"`
			DayOfWeek  int32  `json:"day_of_week"`
			SlotIndex  int32  `json:"slot_index"`
			Parity     string `json:"parity"`
			RoomID     int64  `json:"room_id"`
		} `json:"assignments"`
		Violations []types.Violation `json:"violations"`
		ObjectiveValue float64       `json:"objective_value"`
		SolveTimeMs    int64         `json:"solve_time_ms"`
	}

	if err := json.NewDecoder(resp.Body).Decode(&cpsatResult); err != nil {
		return nil, fmt.Errorf("failed to decode cpsat response: %w", err)
	}

	// Convert to internal assignments
	var assignments []types.Assignment
	for _, a := range cpsatResult.Assignments {
		assignments = append(assignments, types.Assignment{
			ScheduleID: input.ScheduleID,
			ActivityID: a.ActivityID,
			DayOfWeek:  a.DayOfWeek,
			SlotIndex:  a.SlotIndex,
			Parity:     a.Parity,
			RoomID:     a.RoomID,
			Locked:     false,
			Source:     "solver",
		})
	}

	// Save to database
	if err := s.saveResults(ctx, input.TenantID, input.ScheduleID, assignments, cpsatResult.Violations); err != nil {
		log.Printf("Failed to save cpsat results: %v", err)
	}

	solveTimeMs := time.Since(startTime).Milliseconds()

	resultStatus := types.ResultStatus_FEASIBLE
	if cpsatResult.Status == "optimal" {
		resultStatus = types.ResultStatus_OPTIMAL
	} else if cpsatResult.Status == "infeasible" {
		resultStatus = types.ResultStatus_INFEASIBLE
	}

	return &types.ScheduleResult{
		Status:          resultStatus,
		AssignmentIDs:   extractIDs(assignments),
		Violations:      cpsatResult.Violations,
		TotalViolations: int32(len(cpsatResult.Violations)),
		ObjectiveValue:  cpsatResult.ObjectiveValue,
		SolveTimeMs:     solveTimeMs,
	}, nil
}

func (s *Scheduler) optimize(input *types.ScheduleInput, req *types.ScheduleRequest) ([]types.Assignment, []types.Violation) {
	rand.Seed(time.Now().UnixNano())

	var assignments []types.Assignment
	var violations []types.Violation

	roomBusy := make(map[int64]map[string]bool)
	teacherBusy := make(map[int64]map[string]bool)
	groupBusy := make(map[int64]map[string]bool)

	for _, room := range input.Rooms {
		roomBusy[room.ID] = make(map[string]bool)
	}
	for _, teacher := range input.Teachers {
		teacherBusy[teacher.ID] = make(map[string]bool)
	}
	for _, group := range input.Groups {
		groupBusy[group.ID] = make(map[string]bool)
	}

	activities := make([]*types.Activity, len(input.Activities))
	for i := range input.Activities {
		activities[i] = &input.Activities[i]
	}

	sort.Slice(activities, func(i, j int) bool {
		return activities[i].RequiredSlotsPerPeriod > activities[j].RequiredSlotsPerPeriod
	})

	for _, activity := range activities {
		placed := 0
		requiredSlots := int(activity.RequiredSlotsPerPeriod)

		shuffledSlots := make([]int, len(input.TimeSlots))
		for i := range shuffledSlots {
			shuffledSlots[i] = i
		}
		rand.Shuffle(len(shuffledSlots), func(i, j int) {
			shuffledSlots[i], shuffledSlots[j] = shuffledSlots[j], shuffledSlots[i]
		})

		for _, slotIdx := range shuffledSlots {
			if placed >= requiredSlots {
				break
			}

			slot := input.TimeSlots[slotIdx]
			slotKey := fmt.Sprintf("%d_%d_%s", slot.DayOfWeek, slot.SlotIndex, slot.Parity)

			compatibleRooms := s.getCompatibleRooms(input.Rooms, activity)
			rand.Shuffle(len(compatibleRooms), func(i, j int) {
				compatibleRooms[i], compatibleRooms[j] = compatibleRooms[j], compatibleRooms[i]
			})

			for _, room := range compatibleRooms {
				if roomBusy[room.ID][slotKey] {
					continue
				}

				groupAvailable := true
				for _, gid := range activity.GroupIDs {
					if groupBusy[gid][slotKey] {
						groupAvailable = false
						break
					}
				}
				if !groupAvailable {
					continue
				}

				teacherAvailable := true
				for _, tid := range activity.TeacherIDs {
					if teacherBusy[tid][slotKey] {
						teacherAvailable = false
						break
					}
				}
				if !teacherAvailable {
					continue
				}

				if !s.isSlotAvailable(activity, slot, input.Unavailabilities) {
					continue
				}

				assignment := types.Assignment{
					ScheduleID: input.ScheduleID,
					ActivityID: activity.ID,
					DayOfWeek:  slot.DayOfWeek,
					SlotIndex:  slot.SlotIndex,
					Parity:     slot.Parity,
					RoomID:     room.ID,
					Locked:     false,
					Source:     "solver",
				}
				assignments = append(assignments, assignment)

				roomBusy[room.ID][slotKey] = true
				for _, gid := range activity.GroupIDs {
					groupBusy[gid][slotKey] = true
				}
				for _, tid := range activity.TeacherIDs {
					teacherBusy[tid][slotKey] = true
				}

				placed++
				break
			}
		}

		if placed < requiredSlots {
			violations = append(violations, types.Violation{
				ActivityID: activity.ID,
				Code:       "UNPLACED_ACTIVITY",
				Severity:   "hard",
				Meta:       map[string]string{"placed": fmt.Sprint(placed), "required": fmt.Sprint(requiredSlots)},
			})
		}
	}

	violations = append(violations, s.checkWindows(input, groupBusy, teacherBusy)...)
	violations = append(violations, s.checkPreferences(input, assignments, req.Weights)...)

	return assignments, violations
}

func (s *Scheduler) getCompatibleRooms(rooms []types.Room, activity *types.Activity) []types.Room {
	var compatible []types.Room

	for _, room := range rooms {
		if room.Capacity < activity.GroupSize {
			continue
		}

		if len(activity.RoomTypes) == 0 {
			compatible = append(compatible, room)
			continue
		}

		for _, rt := range activity.RoomTypes {
			if rt == room.RoomType || (rt == "lab" && room.RoomType == "pc") {
				compatible = append(compatible, room)
				break
			}
		}
	}

	return compatible
}

func (s *Scheduler) isSlotAvailable(activity *types.Activity, slot types.TimeSlot, unavails []types.Unavailability) bool {
	for _, unav := range unavails {
		if unav.EntityType == "teacher" {
			for _, tid := range activity.TeacherIDs {
				if unav.EntityID == tid &&
					unav.DayOfWeek == slot.DayOfWeek &&
					unav.SlotIndex == slot.SlotIndex &&
					s.matchesParity(unav.Parity, slot.Parity) {
					return false
				}
			}
		}
	}
	return true
}

func (s *Scheduler) matchesParity(unavParity, slotParity string) bool {
	if unavParity == "both" || slotParity == "both" {
		return true
	}
	return unavParity == slotParity
}

func (s *Scheduler) checkWindows(input *types.ScheduleInput, groupBusy, teacherBusy map[int64]map[string]bool) []types.Violation {
	var violations []types.Violation

	for _, activity := range input.Activities {
		for _, groupID := range activity.GroupIDs {
			daySlots := make(map[int32][]int32)
			for key, busy := range groupBusy[groupID] {
				if !busy {
					continue
				}
				parts := strings.Split(key, "_")
				if len(parts) < 3 {
					continue
				}
				var day, slot int32
				fmt.Sscanf(key, "%d_%d", &day, &slot)
				daySlots[day] = append(daySlots[day], slot)
			}

			for day, slots := range daySlots {
				sort.Slice(slots, func(i, j int) bool { return slots[i] < slots[j] })
				for i := 1; i < len(slots); i++ {
					if slots[i]-slots[i-1] > 1 {
						violations = append(violations, types.Violation{
							ActivityID: activity.ID,
							Code:       "GROUP_WINDOW",
							Severity:   "soft",
							Meta:       map[string]string{"day": fmt.Sprint(day), "gap": fmt.Sprint(slots[i] - slots[i-1] - 1)},
						})
					}
				}
			}
		}
	}

	return violations
}

func (s *Scheduler) checkPreferences(input *types.ScheduleInput, assignments []types.Assignment, weights types.Weights) []types.Violation {
	var violations []types.Violation

	for _, pref := range input.Preferences {
		if pref.Weight == 0 {
			continue
		}

		weight := int32(pref.Weight)
		if pref.Weight < 0 {
			weight = -weight * weights.WPrefs
		} else {
			weight = -weight * weights.WPrefs
		}

		for _, assignment := range assignments {
			if assignment.DayOfWeek != pref.DayOfWeek || assignment.SlotIndex != pref.SlotIndex {
				continue
			}

			activity := findActivity(input.Activities, assignment.ActivityID)
			if activity == nil {
				continue
			}

			hasTeacher := false
			for _, tid := range activity.TeacherIDs {
				if tid == pref.TeacherID {
					hasTeacher = true
					break
				}
			}
			if hasTeacher {
				violations = append(violations, types.Violation{
					ActivityID: assignment.ActivityID,
					Code:       "PREFERENCE_VIOLATION",
					Severity:   "soft",
					Meta:       map[string]string{"teacher": fmt.Sprint(pref.TeacherID), "weight": fmt.Sprint(pref.Weight)},
				})
			}
		}
	}

	return violations
}

func (s *Scheduler) saveResults(ctx context.Context, tenantID string, scheduleID int64, assignments []types.Assignment, violations []types.Violation) error {
	if err := s.db.SaveAssignments(ctx, tenantID, scheduleID, assignments); err != nil {
		return fmt.Errorf("failed to save assignments: %w", err)
	}

	if err := s.db.SaveViolations(ctx, tenantID, scheduleID, violations); err != nil {
		return fmt.Errorf("failed to save violations: %w", err)
	}

	log.Printf("Saved %d assignments and %d violations for schedule %d", len(assignments), len(violations), scheduleID)
	return nil
}

func findActivity(activities []types.Activity, id int64) *types.Activity {
	for i := range activities {
		if activities[i].ID == id {
			return &activities[i]
		}
	}
	return nil
}

func extractIDs(assignments []types.Assignment) []int64 {
	ids := make([]int64, len(assignments))
	for i, a := range assignments {
		ids[i] = a.ActivityID
	}
	return ids
}
