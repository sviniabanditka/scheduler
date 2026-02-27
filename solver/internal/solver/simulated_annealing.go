package solver

import (
	"context"
	"fmt"
	"log"
	"math"
	"math/rand"
	"time"

	"scheduler/pkg/types"
)

const (
	saInitialTemp = 100.0
	saCoolingRate  = 0.9995
	saMinTemp      = 0.01
)

// solveAnnealing runs Simulated Annealing starting from a greedy solution.
func (s *Scheduler) solveAnnealing(ctx context.Context, input *types.ScheduleInput, req *types.ScheduleRequest, startTime time.Time) (*types.ScheduleResult, error) {
	// 1. Run greedy for initial solution
	greedyAssignments, _ := s.optimize(input, req)

	if len(greedyAssignments) == 0 {
		return &types.ScheduleResult{
			Status:      types.ResultStatus_INFEASIBLE,
			SolveTimeMs: time.Since(startTime).Milliseconds(),
		}, nil
	}

	log.Printf("SA: greedy produced %d assignments, starting annealing...", len(greedyAssignments))

	// 2. Build SA state
	state := buildState(input, greedyAssignments, req.Weights)

	bestAssignments := make([]types.Assignment, len(state.Assignments))
	copy(bestAssignments, state.Assignments)
	bestScore := state.Score

	log.Printf("SA: initial score=%.2f", bestScore)

	// 3. SA loop
	rng := rand.New(rand.NewSource(time.Now().UnixNano()))
	temp := saInitialTemp
	iterPerTemp := len(input.Activities) * 2
	if iterPerTemp < 10 {
		iterPerTemp = 10
	}

	totalIter := 0
	accepted := 0
	improved := 0

	for temp > saMinTemp {
		// Check context timeout
		select {
		case <-ctx.Done():
			log.Printf("SA: timeout after %d iterations, temp=%.4f", totalIter, temp)
			goto done
		default:
		}

		for iter := 0; iter < iterPerTemp; iter++ {
			totalIter++

			move := randomMove(state, rng)
			if move == nil {
				continue
			}

			oldScore := state.Score
			state.applyMove(move)
			newScore := calculateScore(input, state.Assignments, req.Weights)

			delta := newScore - oldScore

			if delta < 0 || rng.Float64() < math.Exp(-delta/temp) {
				// Accept move
				state.Score = newScore
				accepted++

				if newScore < bestScore {
					bestScore = newScore
					bestAssignments = make([]types.Assignment, len(state.Assignments))
					copy(bestAssignments, state.Assignments)
					improved++
				}
			} else {
				// Reject move
				state.undoMove(move)
			}
		}

		temp *= saCoolingRate
	}

done:
	log.Printf("SA: finished after %d iterations, accepted=%d, improved=%d, bestScore=%.2f",
		totalIter, accepted, improved, bestScore)

	// Build violations from best solution
	violations := s.buildViolations(input, bestAssignments, req.Weights)

	// Save results
	if err := s.saveResults(ctx, input.TenantID, req.ScheduleID, bestAssignments, violations); err != nil {
		log.Printf("Failed to save SA results: %v", err)
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

// buildViolations creates violation records from the solution.
func (s *Scheduler) buildViolations(input *types.ScheduleInput, assignments []types.Assignment, weights types.Weights) []types.Violation {
	var violations []types.Violation

	// Check unplaced
	placedCounts := make(map[int64]int)
	for _, a := range assignments {
		placedCounts[a.ActivityID]++
	}
	for _, act := range input.Activities {
		placed := placedCounts[act.ID]
		required := int(act.RequiredSlotsPerPeriod)
		if placed < required {
			violations = append(violations, types.Violation{
				ActivityID: act.ID,
				Code:       "UNPLACED_ACTIVITY",
				Severity:   "hard",
				Meta:       map[string]string{"placed": fmt.Sprint(placed), "required": fmt.Sprint(required)},
			})
		}
	}

	// Window gaps
	gaps := countWindowGaps(input, assignments)
	if gaps > 0 {
		violations = append(violations, types.Violation{
			Code:     "WINDOW_GAPS",
			Severity: "soft",
			Meta:     map[string]string{"count": fmt.Sprint(gaps)},
		})
	}

	return violations
}
