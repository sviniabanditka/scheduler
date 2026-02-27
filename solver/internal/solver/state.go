package solver

import (
	"math/rand"

	"scheduler/pkg/types"
)

// SAState holds the current state for metaheuristic solvers (SA, Tabu).
type SAState struct {
	Input       *types.ScheduleInput
	Assignments []types.Assignment
	Score       float64
	Weights     types.Weights

	// Busy maps for fast conflict checking: key = "day_slot_parity"
	RoomBusy    map[int64]map[string]bool
	TeacherBusy map[int64]map[string]bool
	GroupBusy   map[int64]map[string]bool

	// Activity -> index in Assignments
	ActivityAssignments map[int64][]int
}

// MoveType defines the type of neighborhood move
type MoveType int

const (
	MoveSwapSlot    MoveType = iota // Change day/slot of one assignment
	MoveSwapRoom    MoveType = iota // Change room of one assignment
	MoveSwapTwo     MoveType = iota // Swap slots between two assignments
	MoveUnplace     MoveType = iota // Remove an assignment
	MoveReplace     MoveType = iota // Remove and re-place an assignment in a new slot
)

// Move represents a neighborhood move
type Move struct {
	Type     MoveType
	Idx1     int   // Index in assignments
	Idx2     int   // Index for swap (MoveSwapTwo only)
	OldDay   int32
	OldSlot  int32
	OldRoom  int64
	OldParity string
	NewDay   int32
	NewSlot  int32
	NewRoom  int64
	NewParity string
	// For SwapTwo
	Old2Day   int32
	Old2Slot  int32
	Old2Room  int64
	Old2Parity string
}

// buildState creates an SAState from greedy output
func buildState(input *types.ScheduleInput, assignments []types.Assignment, weights types.Weights) *SAState {
	st := &SAState{
		Input:       input,
		Assignments: make([]types.Assignment, len(assignments)),
		Weights:     weights,
		RoomBusy:    make(map[int64]map[string]bool),
		TeacherBusy: make(map[int64]map[string]bool),
		GroupBusy:   make(map[int64]map[string]bool),
		ActivityAssignments: make(map[int64][]int),
	}

	copy(st.Assignments, assignments)

	actMap := make(map[int64]*types.Activity)
	for i := range input.Activities {
		actMap[input.Activities[i].ID] = &input.Activities[i]
	}

	for i, a := range st.Assignments {
		key := slotKey(a.DayOfWeek, a.SlotIndex, a.Parity)

		if st.RoomBusy[a.RoomID] == nil {
			st.RoomBusy[a.RoomID] = make(map[string]bool)
		}
		st.RoomBusy[a.RoomID][key] = true

		act := actMap[a.ActivityID]
		if act != nil {
			for _, tid := range act.TeacherIDs {
				if st.TeacherBusy[tid] == nil {
					st.TeacherBusy[tid] = make(map[string]bool)
				}
				st.TeacherBusy[tid][key] = true
			}
			for _, gid := range act.GroupIDs {
				if st.GroupBusy[gid] == nil {
					st.GroupBusy[gid] = make(map[string]bool)
				}
				st.GroupBusy[gid][key] = true
			}
		}

		st.ActivityAssignments[a.ActivityID] = append(st.ActivityAssignments[a.ActivityID], i)
	}

	st.Score = calculateScore(input, st.Assignments, weights)

	return st
}

// isFeasible checks if a slot is available for an activity (no hard constraint violations)
func (st *SAState) isFeasible(act *types.Activity, day, slot int32, parity string, roomID int64, excludeIdx int) bool {
	key := slotKey(day, slot, parity)
	bothKey := slotKey(day, slot, "both")

	// Check room
	if roomID > 0 {
		if parity == "both" {
			if st.RoomBusy[roomID][key] || st.RoomBusy[roomID][slotKey(day, slot, "num")] || st.RoomBusy[roomID][slotKey(day, slot, "den")] {
				if !st.isExcludedSlot(roomID, day, slot, parity, excludeIdx, "room") {
					return false
				}
			}
		} else {
			if st.RoomBusy[roomID][key] || st.RoomBusy[roomID][bothKey] {
				if !st.isExcludedSlot(roomID, day, slot, parity, excludeIdx, "room") {
					return false
				}
			}
		}
	}

	// Check teachers
	for _, tid := range act.TeacherIDs {
		if parity == "both" {
			if st.TeacherBusy[tid][key] || st.TeacherBusy[tid][slotKey(day, slot, "num")] || st.TeacherBusy[tid][slotKey(day, slot, "den")] {
				if !st.isExcludedSlot(tid, day, slot, parity, excludeIdx, "teacher") {
					return false
				}
			}
		} else {
			if st.TeacherBusy[tid][key] || st.TeacherBusy[tid][bothKey] {
				if !st.isExcludedSlot(tid, day, slot, parity, excludeIdx, "teacher") {
					return false
				}
			}
		}
	}

	// Check groups
	for _, gid := range act.GroupIDs {
		if parity == "both" {
			if st.GroupBusy[gid][key] || st.GroupBusy[gid][slotKey(day, slot, "num")] || st.GroupBusy[gid][slotKey(day, slot, "den")] {
				if !st.isExcludedSlot(gid, day, slot, parity, excludeIdx, "group") {
					return false
				}
			}
		} else {
			if st.GroupBusy[gid][key] || st.GroupBusy[gid][bothKey] {
				if !st.isExcludedSlot(gid, day, slot, parity, excludeIdx, "group") {
					return false
				}
			}
		}
	}

	// Check unavailabilities
	for _, unav := range st.Input.Unavailabilities {
		if unav.DayOfWeek != day || unav.SlotIndex != slot {
			continue
		}
		if !parityConflicts(unav.Parity, parity) {
			continue
		}
		if unav.EntityType == "teacher" {
			for _, tid := range act.TeacherIDs {
				if unav.EntityID == tid {
					return false
				}
			}
		}
	}

	return true
}

// isExcludedSlot checks if the busy flag is from the assignment we're moving (excludeIdx)
func (st *SAState) isExcludedSlot(entityID int64, day, slot int32, parity string, excludeIdx int, entityType string) bool {
	if excludeIdx < 0 || excludeIdx >= len(st.Assignments) {
		return false
	}
	a := st.Assignments[excludeIdx]
	if a.DayOfWeek != day || a.SlotIndex != slot || !parityConflicts(a.Parity, parity) {
		return false
	}
	act := st.getActivity(a.ActivityID)
	if act == nil {
		return false
	}

	switch entityType {
	case "room":
		return a.RoomID == entityID
	case "teacher":
		for _, tid := range act.TeacherIDs {
			if tid == entityID {
				return true
			}
		}
	case "group":
		for _, gid := range act.GroupIDs {
			if gid == entityID {
				return true
			}
		}
	}
	return false
}

func (st *SAState) getActivity(id int64) *types.Activity {
	for i := range st.Input.Activities {
		if st.Input.Activities[i].ID == id {
			return &st.Input.Activities[i]
		}
	}
	return nil
}

// removeFromBusy removes an assignment's slots from the busy maps
func (st *SAState) removeFromBusy(a types.Assignment) {
	key := slotKey(a.DayOfWeek, a.SlotIndex, a.Parity)
	delete(st.RoomBusy[a.RoomID], key)

	act := st.getActivity(a.ActivityID)
	if act == nil {
		return
	}
	for _, tid := range act.TeacherIDs {
		delete(st.TeacherBusy[tid], key)
	}
	for _, gid := range act.GroupIDs {
		delete(st.GroupBusy[gid], key)
	}
}

// addToBusy adds an assignment's slots to the busy maps
func (st *SAState) addToBusy(a types.Assignment) {
	key := slotKey(a.DayOfWeek, a.SlotIndex, a.Parity)
	if st.RoomBusy[a.RoomID] == nil {
		st.RoomBusy[a.RoomID] = make(map[string]bool)
	}
	st.RoomBusy[a.RoomID][key] = true

	act := st.getActivity(a.ActivityID)
	if act == nil {
		return
	}
	for _, tid := range act.TeacherIDs {
		if st.TeacherBusy[tid] == nil {
			st.TeacherBusy[tid] = make(map[string]bool)
		}
		st.TeacherBusy[tid][key] = true
	}
	for _, gid := range act.GroupIDs {
		if st.GroupBusy[gid] == nil {
			st.GroupBusy[gid] = make(map[string]bool)
		}
		st.GroupBusy[gid][key] = true
	}
}

// applyMove applies a move in place and returns the move info for undoing
func (st *SAState) applyMove(m *Move) {
	switch m.Type {
	case MoveSwapSlot:
		a := &st.Assignments[m.Idx1]
		st.removeFromBusy(*a)
		a.DayOfWeek = m.NewDay
		a.SlotIndex = m.NewSlot
		a.Parity = m.NewParity
		st.addToBusy(*a)

	case MoveSwapRoom:
		a := &st.Assignments[m.Idx1]
		st.removeFromBusy(*a)
		a.RoomID = m.NewRoom
		st.addToBusy(*a)

	case MoveSwapTwo:
		a1 := &st.Assignments[m.Idx1]
		a2 := &st.Assignments[m.Idx2]
		st.removeFromBusy(*a1)
		st.removeFromBusy(*a2)
		a1.DayOfWeek, a2.DayOfWeek = a2.DayOfWeek, a1.DayOfWeek
		a1.SlotIndex, a2.SlotIndex = a2.SlotIndex, a1.SlotIndex
		a1.Parity, a2.Parity = a2.Parity, a1.Parity
		st.addToBusy(*a1)
		st.addToBusy(*a2)
	}
}

// undoMove reverts a move
func (st *SAState) undoMove(m *Move) {
	switch m.Type {
	case MoveSwapSlot:
		a := &st.Assignments[m.Idx1]
		st.removeFromBusy(*a)
		a.DayOfWeek = m.OldDay
		a.SlotIndex = m.OldSlot
		a.Parity = m.OldParity
		st.addToBusy(*a)

	case MoveSwapRoom:
		a := &st.Assignments[m.Idx1]
		st.removeFromBusy(*a)
		a.RoomID = m.OldRoom
		st.addToBusy(*a)

	case MoveSwapTwo:
		a1 := &st.Assignments[m.Idx1]
		a2 := &st.Assignments[m.Idx2]
		st.removeFromBusy(*a1)
		st.removeFromBusy(*a2)
		a1.DayOfWeek, a2.DayOfWeek = a2.DayOfWeek, a1.DayOfWeek
		a1.SlotIndex, a2.SlotIndex = a2.SlotIndex, a1.SlotIndex
		a1.Parity, a2.Parity = a2.Parity, a1.Parity
		st.addToBusy(*a1)
		st.addToBusy(*a2)
	}
}

// randomSlot returns a random time slot from the input
func randomSlot(input *types.ScheduleInput, rng *rand.Rand) types.TimeSlot {
	return input.TimeSlots[rng.Intn(len(input.TimeSlots))]
}

// randomMove generates a random feasible move
func randomMove(st *SAState, rng *rand.Rand) *Move {
	if len(st.Assignments) == 0 {
		return nil
	}

	for attempts := 0; attempts < 50; attempts++ {
		moveType := rng.Intn(3) // 0=SwapSlot, 1=SwapRoom, 2=SwapTwo

		switch moveType {
		case 0: // SwapSlot
			idx := rng.Intn(len(st.Assignments))
			a := st.Assignments[idx]
			if a.Locked {
				continue
			}
			act := st.getActivity(a.ActivityID)
			if act == nil {
				continue
			}

			slot := randomSlot(st.Input, rng)
			if slot.DayOfWeek == a.DayOfWeek && slot.SlotIndex == a.SlotIndex && slot.Parity == a.Parity {
				continue
			}

			if st.isFeasible(act, slot.DayOfWeek, slot.SlotIndex, slot.Parity, a.RoomID, idx) {
				return &Move{
					Type:      MoveSwapSlot,
					Idx1:      idx,
					OldDay:    a.DayOfWeek,
					OldSlot:   a.SlotIndex,
					OldParity: a.Parity,
					NewDay:    slot.DayOfWeek,
					NewSlot:   slot.SlotIndex,
					NewParity: slot.Parity,
				}
			}

		case 1: // SwapRoom
			idx := rng.Intn(len(st.Assignments))
			a := st.Assignments[idx]
			if a.Locked {
				continue
			}
			act := st.getActivity(a.ActivityID)
			if act == nil {
				continue
			}

			compatRooms := getCompatibleRooms(st.Input.Rooms, act)
			if len(compatRooms) <= 1 {
				continue
			}
			newRoom := compatRooms[rng.Intn(len(compatRooms))]
			if newRoom.ID == a.RoomID {
				continue
			}

			if st.isFeasible(act, a.DayOfWeek, a.SlotIndex, a.Parity, newRoom.ID, idx) {
				return &Move{
					Type:    MoveSwapRoom,
					Idx1:    idx,
					OldRoom: a.RoomID,
					NewRoom: newRoom.ID,
				}
			}

		case 2: // SwapTwo
			if len(st.Assignments) < 2 {
				continue
			}
			idx1 := rng.Intn(len(st.Assignments))
			idx2 := rng.Intn(len(st.Assignments))
			if idx1 == idx2 {
				continue
			}
			a1 := st.Assignments[idx1]
			a2 := st.Assignments[idx2]
			if a1.Locked || a2.Locked {
				continue
			}
			if a1.DayOfWeek == a2.DayOfWeek && a1.SlotIndex == a2.SlotIndex && a1.Parity == a2.Parity {
				continue
			}

			act1 := st.getActivity(a1.ActivityID)
			act2 := st.getActivity(a2.ActivityID)
			if act1 == nil || act2 == nil {
				continue
			}

			// Temporarily remove both
			st.removeFromBusy(a1)
			st.removeFromBusy(a2)

			feasible1 := st.isFeasible(act1, a2.DayOfWeek, a2.SlotIndex, a2.Parity, a1.RoomID, -1)
			feasible2 := st.isFeasible(act2, a1.DayOfWeek, a1.SlotIndex, a1.Parity, a2.RoomID, -1)

			// Re-add both
			st.addToBusy(a1)
			st.addToBusy(a2)

			if feasible1 && feasible2 {
				return &Move{
					Type:       MoveSwapTwo,
					Idx1:       idx1,
					Idx2:       idx2,
					OldDay:     a1.DayOfWeek,
					OldSlot:    a1.SlotIndex,
					OldParity:  a1.Parity,
					Old2Day:    a2.DayOfWeek,
					Old2Slot:   a2.SlotIndex,
					Old2Parity: a2.Parity,
				}
			}
		}
	}

	return nil
}

func getCompatibleRooms(rooms []types.Room, activity *types.Activity) []types.Room {
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

