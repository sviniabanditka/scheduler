package solver

import (
	"fmt"
	"math"
	"sort"

	"scheduler/pkg/types"
)

// calculateScore computes the total objective value for a set of assignments.
// Lower score = better solution.
func calculateScore(input *types.ScheduleInput, assignments []types.Assignment, weights types.Weights) float64 {
	score := 0.0

	// Penalty for unplaced activities
	score += countUnplacedPenalty(input, assignments) * 1000.0

	// Window gaps
	if weights.WWindows > 0 {
		score += float64(countWindowGaps(input, assignments)) * weights.WWindows
	}

	// Preference violations
	if weights.WPrefs > 0 {
		score += evalPreferenceRules(input, assignments) * weights.WPrefs
	}

	// Balance
	if weights.WBalance > 0 {
		score += evalBalance(input, assignments) * weights.WBalance
	}

	return score
}

// countUnplacedPenalty returns penalty based on how many required slots are unplaced.
func countUnplacedPenalty(input *types.ScheduleInput, assignments []types.Assignment) float64 {
	placedCounts := make(map[int64]int)
	for _, a := range assignments {
		placedCounts[a.ActivityID]++
	}

	penalty := 0.0
	for _, act := range input.Activities {
		placed := placedCounts[act.ID]
		required := int(act.RequiredSlotsPerPeriod)
		if placed < required {
			penalty += float64(required - placed)
		}
	}
	return penalty
}

// countWindowGaps counts the number of gap slots (empty slots between occupied ones)
// for all groups and teachers on each day.
func countWindowGaps(input *types.ScheduleInput, assignments []types.Assignment) int {
	// Build activity lookup
	actMap := make(map[int64]*types.Activity)
	for i := range input.Activities {
		actMap[input.Activities[i].ID] = &input.Activities[i]
	}

	// Group slots
	groupSlots := make(map[int64]map[dayParityKey][]int32) // groupID -> {day,parity} -> slot indices
	teacherSlots := make(map[int64]map[dayParityKey][]int32)

	allParities := collectParities(input)
	effectiveParities := []string{"both"}
	if contains(allParities, "num") || contains(allParities, "den") {
		effectiveParities = []string{"num", "den"}
	}

	for _, a := range assignments {
		act := actMap[a.ActivityID]
		if act == nil {
			continue
		}

		for _, ep := range effectiveParities {
			if a.Parity != ep && a.Parity != "both" {
				continue
			}
			dpk := dayParityKey{day: a.DayOfWeek, parity: ep}

			for _, gid := range act.GroupIDs {
				if groupSlots[gid] == nil {
					groupSlots[gid] = make(map[dayParityKey][]int32)
				}
				groupSlots[gid][dpk] = append(groupSlots[gid][dpk], a.SlotIndex)
			}
			for _, tid := range act.TeacherIDs {
				if teacherSlots[tid] == nil {
					teacherSlots[tid] = make(map[dayParityKey][]int32)
				}
				teacherSlots[tid][dpk] = append(teacherSlots[tid][dpk], a.SlotIndex)
			}
		}
	}

	totalGaps := 0
	totalGaps += countGapsInMap(groupSlots)
	totalGaps += countGapsInMap(teacherSlots)

	return totalGaps
}

type dayParityKey struct {
	day    int32
	parity string
}

func countGapsInMap(slotsMap map[int64]map[dayParityKey][]int32) int {
	gaps := 0
	for _, dayMap := range slotsMap {
		for _, slots := range dayMap {
			if len(slots) < 2 {
				continue
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
			if len(deduped) >= 2 {
				for i := 1; i < len(deduped); i++ {
					gap := deduped[i] - deduped[i-1] - 1
					if gap > 0 {
						gaps += gap
					}
				}
			}
		}
	}
	return gaps
}

func collectParities(input *types.ScheduleInput) []string {
	set := make(map[string]bool)
	for _, s := range input.TimeSlots {
		set[s.Parity] = true
	}
	var result []string
	for p := range set {
		result = append(result, p)
	}
	return result
}

func contains(s []string, e string) bool {
	for _, a := range s {
		if a == e {
			return true
		}
	}
	return false
}

// evalPreferenceRules evaluates preference-based penalties.
func evalPreferenceRules(input *types.ScheduleInput, assignments []types.Assignment) float64 {
	actMap := make(map[int64]*types.Activity)
	for i := range input.Activities {
		actMap[input.Activities[i].ID] = &input.Activities[i]
	}

	// Build teacher -> assignments mapping
	teacherAssignments := make(map[int64][]types.Assignment)
	for _, a := range assignments {
		act := actMap[a.ActivityID]
		if act == nil {
			continue
		}
		for _, tid := range act.TeacherIDs {
			teacherAssignments[tid] = append(teacherAssignments[tid], a)
		}
	}

	penalty := 0.0

	// Legacy preferences
	for _, pref := range input.Preferences {
		if pref.Weight >= 0 {
			continue // only penalize negative (avoid) preferences
		}
		for _, a := range teacherAssignments[pref.TeacherID] {
			if a.DayOfWeek == pref.DayOfWeek && a.SlotIndex == pref.SlotIndex {
				if parityMatches(pref.Parity, a.Parity) {
					penalty += float64(abs32(pref.Weight))
				}
			}
		}
	}

	// Preference rules
	for _, rule := range input.PreferenceRules {
		if !rule.IsActive {
			continue
		}
		switch rule.RuleType {
		case "preferred_slot":
			day := getIntParam(rule.Params, "day_of_week")
			slotIdx := getIntParam(rule.Params, "slot_index")
			if day == nil || slotIdx == nil {
				continue
			}
			// Bonus (negative penalty) for using preferred slot
			for _, a := range teacherAssignments[rule.TeacherID] {
				if a.DayOfWeek == int32(*day) && a.SlotIndex == int32(*slotIdx) {
					penalty -= float64(rule.Weight) / 10.0
				}
			}

		case "min_start_slot":
			minSlot := getIntParam(rule.Params, "min_slot")
			day := getIntParam(rule.Params, "day_of_week")
			if minSlot == nil {
				continue
			}
			for _, a := range teacherAssignments[rule.TeacherID] {
				if a.SlotIndex < int32(*minSlot) {
					if day == nil || a.DayOfWeek == int32(*day) {
						penalty += float64(rule.Weight)
					}
				}
			}

		case "max_end_slot":
			maxSlot := getIntParam(rule.Params, "max_slot")
			day := getIntParam(rule.Params, "day_of_week")
			if maxSlot == nil {
				continue
			}
			for _, a := range teacherAssignments[rule.TeacherID] {
				if a.SlotIndex > int32(*maxSlot) {
					if day == nil || a.DayOfWeek == int32(*day) {
						penalty += float64(rule.Weight)
					}
				}
			}

		case "max_hours_per_day":
			maxHours := getIntParam(rule.Params, "max_hours")
			if maxHours == nil {
				continue
			}
			dayLoads := make(map[int32]int)
			for _, a := range teacherAssignments[rule.TeacherID] {
				dayLoads[a.DayOfWeek]++
			}
			for _, load := range dayLoads {
				excess := load - *maxHours
				if excess > 0 {
					penalty += float64(excess) * float64(rule.Weight)
				}
			}
		}
	}

	return penalty
}

// evalBalance computes max-min daily load difference for groups and teachers.
func evalBalance(input *types.ScheduleInput, assignments []types.Assignment) float64 {
	actMap := make(map[int64]*types.Activity)
	for i := range input.Activities {
		actMap[input.Activities[i].ID] = &input.Activities[i]
	}

	allDays := collectDays(input)

	// Group balance
	groupDayLoad := make(map[int64]map[int32]int)
	teacherDayLoad := make(map[int64]map[int32]int)

	for _, a := range assignments {
		act := actMap[a.ActivityID]
		if act == nil {
			continue
		}
		for _, gid := range act.GroupIDs {
			if groupDayLoad[gid] == nil {
				groupDayLoad[gid] = make(map[int32]int)
			}
			groupDayLoad[gid][a.DayOfWeek]++
		}
		for _, tid := range act.TeacherIDs {
			if teacherDayLoad[tid] == nil {
				teacherDayLoad[tid] = make(map[int32]int)
			}
			teacherDayLoad[tid][a.DayOfWeek]++
		}
	}

	penalty := 0.0

	penalty += balancePenalty(groupDayLoad, allDays)
	penalty += balancePenalty(teacherDayLoad, allDays)

	return penalty
}

func balancePenalty(entityDayLoad map[int64]map[int32]int, allDays []int32) float64 {
	penalty := 0.0
	for _, dayMap := range entityDayLoad {
		if len(dayMap) == 0 {
			continue
		}
		maxLoad, minLoad := 0, math.MaxInt32
		for _, day := range allDays {
			load := dayMap[day]
			if load > maxLoad {
				maxLoad = load
			}
			if load < minLoad {
				minLoad = load
			}
		}
		diff := maxLoad - minLoad
		if diff > 0 {
			penalty += float64(diff)
		}
	}
	return penalty
}

func collectDays(input *types.ScheduleInput) []int32 {
	set := make(map[int32]bool)
	for _, s := range input.TimeSlots {
		set[s.DayOfWeek] = true
	}
	var days []int32
	for d := range set {
		days = append(days, d)
	}
	sort.Slice(days, func(i, j int) bool { return days[i] < days[j] })
	return days
}

func parityMatches(p1, p2 string) bool {
	if p1 == "both" || p2 == "both" {
		return true
	}
	return p1 == p2
}

func abs32(x int32) int32 {
	if x < 0 {
		return -x
	}
	return x
}

func getIntParam(params map[string]interface{}, key string) *int {
	v, ok := params[key]
	if !ok {
		return nil
	}
	switch val := v.(type) {
	case float64:
		i := int(val)
		return &i
	case int:
		return &val
	case int64:
		i := int(val)
		return &i
	case string:
		var i int
		if _, err := fmt.Sscanf(val, "%d", &i); err == nil {
			return &i
		}
	}
	return nil
}

// slotKey returns a unique string key for a time slot assignment
func slotKey(day int32, slot int32, parity string) string {
	return fmt.Sprintf("%d_%d_%s", day, slot, parity)
}

// parityConflicts checks if two parity values conflict
func parityConflicts(p1, p2 string) bool {
	if p1 == "both" || p2 == "both" {
		return true
	}
	return p1 == p2
}

