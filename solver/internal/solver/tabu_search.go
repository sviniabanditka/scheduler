package solver

import (
	"context"
	"log"
	"math/rand"
	"time"

	"scheduler/pkg/types"
)

const (
	tabuListSize     = 50
	tabuNeighborhood = 30 // Number of neighbors to evaluate per iteration
)

// tabuMoveKey uniquely identifies a move for the tabu list
type tabuMoveKey struct {
	Idx     int
	NewDay  int32
	NewSlot int32
}

// solveTabuSearch runs Tabu Search starting from a greedy solution.
func (s *Scheduler) solveTabuSearch(ctx context.Context, input *types.ScheduleInput, req *types.ScheduleRequest, startTime time.Time) (*types.ScheduleResult, error) {
	// 1. Run greedy for initial solution
	greedyAssignments, _ := s.optimize(input, req)

	if len(greedyAssignments) == 0 {
		return &types.ScheduleResult{
			Status:      types.ResultStatus_INFEASIBLE,
			SolveTimeMs: time.Since(startTime).Milliseconds(),
		}, nil
	}

	log.Printf("Tabu: greedy produced %d assignments, starting tabu search...", len(greedyAssignments))

	// 2. Build state
	state := buildState(input, greedyAssignments, req.Weights)

	bestAssignments := make([]types.Assignment, len(state.Assignments))
	copy(bestAssignments, state.Assignments)
	bestScore := state.Score

	log.Printf("Tabu: initial score=%.2f", bestScore)

	// 3. Tabu list (ring buffer)
	tabuList := make([]tabuMoveKey, 0, tabuListSize)

	rng := rand.New(rand.NewSource(time.Now().UnixNano()))
	totalIter := 0
	improved := 0

	for {
		// Check context timeout
		select {
		case <-ctx.Done():
			log.Printf("Tabu: timeout after %d iterations", totalIter)
			goto done
		default:
		}

		totalIter++

		// Generate and evaluate neighbors
		var bestMove *Move
		bestMoveScore := state.Score + 1e9
		var bestMoveKey tabuMoveKey

		for n := 0; n < tabuNeighborhood; n++ {
			move := randomMove(state, rng)
			if move == nil {
				continue
			}

			// Apply move
			state.applyMove(move)
			newScore := calculateScore(input, state.Assignments, req.Weights)

			// Check if tabu
			mk := moveToKey(move)
			isTabu := isInTabuList(tabuList, mk)

			// Accept if: (a) not tabu and better than current best neighbor, or
			//             (b) tabu but meets aspiration criterion (better than global best)
			if newScore < bestMoveScore {
				if !isTabu || newScore < bestScore {
					bestMove = move
					bestMoveScore = newScore
					bestMoveKey = mk
				}
			}

			// Undo move
			state.undoMove(move)
		}

		if bestMove == nil {
			// No feasible moves found, try again
			if totalIter > 10000 {
				break
			}
			continue
		}

		// Apply best move
		state.applyMove(bestMove)
		state.Score = bestMoveScore

		// Add to tabu list
		tabuList = append(tabuList, bestMoveKey)
		if len(tabuList) > tabuListSize {
			tabuList = tabuList[1:]
		}

		// Update best
		if bestMoveScore < bestScore {
			bestScore = bestMoveScore
			bestAssignments = make([]types.Assignment, len(state.Assignments))
			copy(bestAssignments, state.Assignments)
			improved++
		}

		// Limit total iterations to prevent running forever
		if totalIter >= 50000 {
			break
		}
	}

done:
	log.Printf("Tabu: finished after %d iterations, improved=%d, bestScore=%.2f",
		totalIter, improved, bestScore)

	// Build violations
	violations := s.buildViolations(input, bestAssignments, req.Weights)

	// Save results
	if err := s.saveResults(ctx, input.TenantID, req.ScheduleID, bestAssignments, violations); err != nil {
		log.Printf("Failed to save Tabu results: %v", err)
	}

	return &types.ScheduleResult{
		Status:          types.ResultStatus_FEASIBLE,
		AssignmentIDs:   extractIDs(bestAssignments),
		Violations:      violations,
		TotalViolations: int32(len(violations)),
		ObjectiveValue:  bestScore,
		SolveTimeMs:     time.Since(startTime).Milliseconds(),
	}, nil
}

func moveToKey(m *Move) tabuMoveKey {
	return tabuMoveKey{
		Idx:     m.Idx1,
		NewDay:  m.NewDay,
		NewSlot: m.NewSlot,
	}
}

func isInTabuList(list []tabuMoveKey, key tabuMoveKey) bool {
	for _, k := range list {
		if k == key {
			return true
		}
	}
	return false
}

