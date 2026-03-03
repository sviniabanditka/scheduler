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
	saMinTemp = 0.01
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

	// 3. Adaptive SA parameters
	rng := rand.New(rand.NewSource(time.Now().UnixNano()))

	// Initial temperature: ~5% of initial score, so moves that worsen by 5% are accepted ~37% of the time
	temp := bestScore * 0.05
	if temp < 1.0 {
		temp = 1.0
	}
	if temp > 50.0 {
		temp = 50.0
	}

	iterPerTemp := len(input.Activities) * 3
	if iterPerTemp < 20 {
		iterPerTemp = 20
	}

	// Compute cooling rate based on timeout to ensure we reach minTemp
	timeout := int(req.TimeoutSeconds)
	if timeout == 0 {
		timeout = 420
	}
	// Estimate iterations per second from greedy build time
	estimatedTotalSteps := float64(timeout) * 500 // rough estimate: ~500 temp steps per second
	if estimatedTotalSteps < 1000 {
		estimatedTotalSteps = 1000
	}
	coolingRate := math.Pow(saMinTemp/temp, 1.0/estimatedTotalSteps)
	if coolingRate < 0.99 {
		coolingRate = 0.99
	}
	if coolingRate > 0.9999 {
		coolingRate = 0.9999
	}

	log.Printf("SA: adaptive params: temp=%.2f coolingRate=%.6f iterPerTemp=%d", temp, coolingRate, iterPerTemp)

	totalIter := 0
	accepted := 0
	improved := 0
	iterSinceImprove := 0
	reseedCount := 0
	reseedThreshold := iterPerTemp * 200 // Reseed from best if no improvement for this many iterations

	logInterval := iterPerTemp * 100

	for temp > saMinTemp {
		// Check context timeout
		select {
		case <-ctx.Done():
			log.Printf("SA: timeout after %d iterations, temp=%.4f, bestScore=%.2f", totalIter, temp, bestScore)
			goto done
		default:
		}

		for iter := 0; iter < iterPerTemp; iter++ {
			totalIter++
			iterSinceImprove++

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
					iterSinceImprove = 0
				}
			} else {
				// Reject move
				state.undoMove(move)
			}
		}

		// Reseed from best solution if stuck for too long
		if iterSinceImprove > reseedThreshold {
			state = buildState(input, bestAssignments, req.Weights)
			iterSinceImprove = 0
			reseedCount++
			// Slightly reduce temperature to focus search
			temp *= 0.5
		}

		if logInterval > 0 && totalIter%logInterval == 0 {
			log.Printf("SA: iter=%d temp=%.4f score=%.2f best=%.2f accepted=%d improved=%d reseeds=%d",
				totalIter, temp, state.Score, bestScore, accepted, improved, reseedCount)
		}

		temp *= coolingRate
	}

done:
	log.Printf("SA: finished after %d iterations, accepted=%d, improved=%d, reseeds=%d, bestScore=%.2f",
		totalIter, accepted, improved, reseedCount, bestScore)

	// Build violations from best solution
	violations := s.buildViolations(input, bestAssignments, req.Weights)

	// Save results with a fresh context (the solve context may be expired)
	saveCtx, saveCancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer saveCancel()
	if err := s.saveResults(saveCtx, input.TenantID, req.ScheduleID, bestAssignments, violations); err != nil {
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

	// Window gaps (separate for reporting)
	groupGaps, teacherGaps := countWindowGapsSeparate(input, assignments)
	totalGaps := groupGaps + teacherGaps
	if totalGaps > 0 {
		violations = append(violations, types.Violation{
			Code:     "WINDOW_GAPS",
			Severity: "soft",
			Meta:     map[string]string{"group_gaps": fmt.Sprint(groupGaps), "teacher_gaps": fmt.Sprint(teacherGaps), "total": fmt.Sprint(totalGaps)},
		})
	}

	return violations
}
