package solver

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"math"
	"math/rand"
	"net/http"
	"sort"
	"strings"
	"time"

	"scheduler/internal/db"
	"scheduler/pkg/types"
)

type Scheduler struct {
	db *db.PostgresDB
}

func NewScheduler(database *db.PostgresDB) *Scheduler {
	return &Scheduler{
		db: database,
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

	// Reserve 15 seconds for saving results after solve completes
	solveTimeout := timeout - 15
	if solveTimeout < 30 {
		solveTimeout = timeout
	}

	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(solveTimeout)*time.Second)
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

	log.Printf("Solve request: tenant=%s calendar=%d schedule=%d algorithm='%s' activities=%d",
		req.TenantID, req.CalendarID, req.ScheduleID, req.Algorithm, len(input.Activities))

	// Route to appropriate algorithm
	switch req.Algorithm {
	case "annealing", "cpsat":
		log.Printf("Routing to Simulated Annealing solver")
		return s.solveAnnealing(ctx, input, req, startTime)
	case "tabu":
		log.Printf("Routing to Tabu Search solver")
		return s.solveTabuSearch(ctx, input, req, startTime)
	}

	log.Printf("Routing to greedy solver")
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

// busyConflict checks if a slot conflicts with existing busy entries, respecting parity.
// A "both" assignment conflicts with "num" and "den" slots, and vice versa.
func busyConflict(busyMap map[string]bool, day, slot int32, parity string) bool {
	key := fmt.Sprintf("%d_%d_%s", day, slot, parity)
	if busyMap[key] {
		return true
	}
	if parity == "both" {
		return busyMap[fmt.Sprintf("%d_%d_num", day, slot)] || busyMap[fmt.Sprintf("%d_%d_den", day, slot)]
	}
	return busyMap[fmt.Sprintf("%d_%d_both", day, slot)]
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

	// Track group day-parity slot lists for gap scoring
	groupDaySlots := make(map[int64]map[dayParityKey][]int32)

	activities := make([]*types.Activity, len(input.Activities))
	for i := range input.Activities {
		activities[i] = &input.Activities[i]
	}

	sort.Slice(activities, func(i, j int) bool {
		return activities[i].RequiredSlotsPerPeriod > activities[j].RequiredSlotsPerPeriod
	})

	allParities := collectParities(input)
	effectiveParities := []string{"both"}
	if contains(allParities, "num") || contains(allParities, "den") {
		effectiveParities = []string{"num", "den"}
	}

	for _, activity := range activities {
		placed := 0
		requiredSlots := int(activity.RequiredSlotsPerPeriod)

		compatibleRooms := getCompatibleRooms(input.Rooms, activity)

		// For each slot needed, find the best feasible candidate with fresh scores
		for placed < requiredSlots {
			bestScore := math.MaxFloat64
			bestSlotIdx := -1
			bestRoomIdx := -1

			for si, slot := range input.TimeSlots {
				day := slot.DayOfWeek
				slotIndex := slot.SlotIndex
				parity := slot.Parity

				// Check group availability with parity
				groupAvailable := true
				for _, gid := range activity.GroupIDs {
					if busyConflict(groupBusy[gid], day, slotIndex, parity) {
						groupAvailable = false
						break
					}
				}
				if !groupAvailable {
					continue
				}

				// Check teacher availability with parity
				teacherAvailable := true
				for _, tid := range activity.TeacherIDs {
					if busyConflict(teacherBusy[tid], day, slotIndex, parity) {
						teacherAvailable = false
						break
					}
				}
				if !teacherAvailable {
					continue
				}

				// Check unavailabilities (teacher + group; room checked per-room below)
				if !s.isSlotAvailable(activity, slot, input.Unavailabilities, 0) {
					continue
				}

				for ri, room := range compatibleRooms {
					if busyConflict(roomBusy[room.ID], day, slotIndex, parity) {
						continue
					}

					// Check room unavailability
					if !s.isSlotAvailable(activity, slot, input.Unavailabilities, room.ID) {
						continue
					}

					// Score with current state (fresh after each placement)
					score := s.scoreCandidate(activity, day, slotIndex, parity, groupDaySlots, groupBusy, effectiveParities, input, req.Weights)

					if score < bestScore {
						bestScore = score
						bestSlotIdx = si
						bestRoomIdx = ri
					}
				}
			}

			if bestSlotIdx < 0 {
				break // No feasible slot found
			}

			slot := input.TimeSlots[bestSlotIdx]
			room := compatibleRooms[bestRoomIdx]
			slotKeyStr := fmt.Sprintf("%d_%d_%s", slot.DayOfWeek, slot.SlotIndex, slot.Parity)

			assignments = append(assignments, types.Assignment{
				ScheduleID: input.ScheduleID,
				ActivityID: activity.ID,
				DayOfWeek:  slot.DayOfWeek,
				SlotIndex:  slot.SlotIndex,
				Parity:     slot.Parity,
				RoomID:     room.ID,
				Locked:     false,
				Source:     "solver",
			})

			roomBusy[room.ID][slotKeyStr] = true
			for _, gid := range activity.GroupIDs {
				groupBusy[gid][slotKeyStr] = true
				for _, ep := range effectiveParities {
					if slot.Parity != ep && slot.Parity != "both" {
						continue
					}
					dpk := dayParityKey{day: slot.DayOfWeek, parity: ep}
					if groupDaySlots[gid] == nil {
						groupDaySlots[gid] = make(map[dayParityKey][]int32)
					}
					groupDaySlots[gid][dpk] = append(groupDaySlots[gid][dpk], slot.SlotIndex)
				}
			}
			for _, tid := range activity.TeacherIDs {
				teacherBusy[tid][slotKeyStr] = true
			}

			placed++
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

// scoreCandidate computes an incremental score for placing an activity at a given slot.
// Lower score = better placement.
func (s *Scheduler) scoreCandidate(
	activity *types.Activity,
	day, slotIndex int32,
	parity string,
	groupDaySlots map[int64]map[dayParityKey][]int32,
	groupBusy map[int64]map[string]bool,
	effectiveParities []string,
	input *types.ScheduleInput,
	weights types.Weights,
) float64 {
	score := 0.0

	// 1. Gap penalty (quadratic): for each group, how does adding this slot affect gaps on this day/parity?
	for _, gid := range activity.GroupIDs {
		for _, ep := range effectiveParities {
			if parity != ep && parity != "both" {
				continue
			}
			dpk := dayParityKey{day: day, parity: ep}
			existing := groupDaySlots[gid][dpk]

			gapsBefore := computeGapsQuadratic(existing)
			trial := append(append([]int32{}, existing...), slotIndex)
			gapsAfter := computeGapsQuadratic(trial)
			delta := gapsAfter - gapsBefore
			score += delta * weights.WWindows
		}
	}

	// 2. Balance penalty: prefer days with fewer existing assignments for this group
	for _, gid := range activity.GroupIDs {
		dayLoad := 0
		if groupDaySlots[gid] != nil {
			for dpk, slots := range groupDaySlots[gid] {
				if dpk.day == day {
					dayLoad += len(slots)
				}
			}
		}
		score += float64(dayLoad) * weights.WBalance * 0.5
	}

	// 3. Preference penalty: check if this slot violates teacher preferences
	for _, pref := range input.Preferences {
		if pref.Weight >= 0 {
			continue
		}
		if pref.DayOfWeek != day || pref.SlotIndex != slotIndex {
			continue
		}
		for _, tid := range activity.TeacherIDs {
			if tid == pref.TeacherID {
				if parityMatches(pref.Parity, parity) {
					score += float64(abs32(pref.Weight)) * weights.WPrefs * 0.1
				}
			}
		}
	}

	return score
}

// computeGapsQuadratic computes the sum of gap^2 for a set of slot indices.
func computeGapsQuadratic(slots []int32) float64 {
	if len(slots) < 2 {
		return 0
	}
	sorted := make([]int, len(slots))
	for i, s := range slots {
		sorted[i] = int(s)
	}
	sort.Ints(sorted)
	// Deduplicate
	deduped := []int{sorted[0]}
	for i := 1; i < len(sorted); i++ {
		if sorted[i] != sorted[i-1] {
			deduped = append(deduped, sorted[i])
		}
	}
	total := 0.0
	for i := 1; i < len(deduped); i++ {
		gap := deduped[i] - deduped[i-1] - 1
		if gap > 0 {
			total += float64(gap * gap)
		}
	}
	return total
}


func (s *Scheduler) isSlotAvailable(activity *types.Activity, slot types.TimeSlot, unavails []types.Unavailability, roomID int64) bool {
	for _, unav := range unavails {
		if unav.DayOfWeek != slot.DayOfWeek || unav.SlotIndex != slot.SlotIndex {
			continue
		}
		if !s.matchesParity(unav.Parity, slot.Parity) {
			continue
		}
		switch unav.EntityType {
		case "teacher":
			for _, tid := range activity.TeacherIDs {
				if unav.EntityID == tid {
					return false
				}
			}
		case "group":
			for _, gid := range activity.GroupIDs {
				if unav.EntityID == gid {
					return false
				}
			}
		case "room":
			if roomID > 0 && unav.EntityID == roomID {
				return false
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

	// Collect effective parities
	allParities := collectParities(input)
	effectiveParities := []string{"both"}
	if contains(allParities, "num") || contains(allParities, "den") {
		effectiveParities = []string{"num", "den"}
	}

	// Check each group (deduplicated across activities)
	checkedGroups := make(map[int64]bool)
	for _, activity := range input.Activities {
		for _, groupID := range activity.GroupIDs {
			if checkedGroups[groupID] {
				continue
			}
			checkedGroups[groupID] = true

			dayParitySlots := make(map[dayParityKey][]int32)
			for key, busy := range groupBusy[groupID] {
				if !busy {
					continue
				}
				parts := strings.Split(key, "_")
				if len(parts) < 3 {
					continue
				}
				var day, slot int32
				fmt.Sscanf(parts[0], "%d", &day)
				fmt.Sscanf(parts[1], "%d", &slot)
				keyParity := parts[2]

				for _, ep := range effectiveParities {
					if keyParity != ep && keyParity != "both" {
						continue
					}
					dpk := dayParityKey{day: day, parity: ep}
					dayParitySlots[dpk] = append(dayParitySlots[dpk], slot)
				}
			}

			for dpk, slots := range dayParitySlots {
				// Deduplicate and sort
				sort.Slice(slots, func(i, j int) bool { return slots[i] < slots[j] })
				deduped := []int32{slots[0]}
				for i := 1; i < len(slots); i++ {
					if slots[i] != slots[i-1] {
						deduped = append(deduped, slots[i])
					}
				}
				for i := 1; i < len(deduped); i++ {
					gap := deduped[i] - deduped[i-1] - 1
					if gap > 0 {
						violations = append(violations, types.Violation{
							Code:     "GROUP_WINDOW",
							Severity: "soft",
							Meta: map[string]string{
								"group_id": fmt.Sprint(groupID),
								"day":      fmt.Sprint(dpk.day),
								"parity":   dpk.parity,
								"gap":      fmt.Sprint(gap),
							},
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
