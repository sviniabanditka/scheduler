<?php

namespace App\Services;

use App\Models\RescheduleRequest;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RescheduleService
{
    protected ScheduleGenerationService $scheduleService;

    public function __construct(ScheduleGenerationService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Validate that a proposed reschedule has no conflicts
     * Returns array of conflict descriptions (empty = valid)
     */
    public function validateProposal(RescheduleRequest $request): array
    {
        $conflicts = [];
        $assignment = $request->assignment()->with('activity.teachers', 'activity.groups')->first();

        if (!$assignment) {
            return ['Призначення не знайдено'];
        }

        $version = $assignment->scheduleVersion;

        // Check room conflict at proposed time
        if ($request->proposed_room_id) {
            $roomConflict = ScheduleAssignment::withoutGlobalScopes()
                ->where('schedule_version_id', $version->id)
                ->where('room_id', $request->proposed_room_id)
                ->where('day_of_week', $request->proposed_day_of_week)
                ->where('slot_index', $request->proposed_slot_index)
                ->where('id', '!=', $assignment->id)
                ->where(function ($q) use ($request) {
                    $q->where('parity', 'both')
                      ->orWhere('parity', $request->proposed_parity)
                      ->orWhere(fn ($q2) => $q2->where($request->proposed_parity, 'both'));
                })
                ->exists();

            if ($roomConflict) {
                $conflicts[] = 'Аудиторія зайнята в запропонований час';
            }
        }

        // Check teacher conflicts
        $teacherIds = $assignment->activity->teachers->pluck('id')->toArray();
        if (!empty($teacherIds)) {
            $teacherConflict = ScheduleAssignment::withoutGlobalScopes()
                ->where('schedule_version_id', $version->id)
                ->where('day_of_week', $request->proposed_day_of_week)
                ->where('slot_index', $request->proposed_slot_index)
                ->where('id', '!=', $assignment->id)
                ->where(function ($q) use ($request) {
                    $q->where('parity', 'both')
                      ->orWhere('parity', $request->proposed_parity)
                      ->orWhere(fn ($q2) => $q2->where($request->proposed_parity, 'both'));
                })
                ->whereHas('activity.teachers', function ($q) use ($teacherIds) {
                    $q->whereIn('teachers.id', $teacherIds);
                })
                ->exists();

            if ($teacherConflict) {
                $conflicts[] = 'Викладач зайнятий в запропонований час';
            }
        }

        // Check group conflicts
        $groupIds = $assignment->activity->groups->pluck('id')->toArray();
        if (!empty($groupIds)) {
            $groupConflict = ScheduleAssignment::withoutGlobalScopes()
                ->where('schedule_version_id', $version->id)
                ->where('day_of_week', $request->proposed_day_of_week)
                ->where('slot_index', $request->proposed_slot_index)
                ->where('id', '!=', $assignment->id)
                ->where(function ($q) use ($request) {
                    $q->where('parity', 'both')
                      ->orWhere('parity', $request->proposed_parity)
                      ->orWhere(fn ($q2) => $q2->where($request->proposed_parity, 'both'));
                })
                ->whereHas('activity.groups', function ($q) use ($groupIds) {
                    $q->whereIn('groups.id', $groupIds);
                })
                ->exists();

            if ($groupConflict) {
                $conflicts[] = 'Група зайнята в запропонований час';
            }
        }

        return $conflicts;
    }

    /**
     * Approve a reschedule request: create new version with moved assignment
     */
    public function approve(RescheduleRequest $request, User $admin, ?string $comment = null): ScheduleVersion
    {
        return DB::transaction(function () use ($request, $admin, $comment) {
            $assignment = $request->assignment()->with('scheduleVersion')->first();
            $currentVersion = $assignment->scheduleVersion;

            // Find next version number
            $maxVersion = ScheduleVersion::withoutGlobalScopes()
                ->where('tenant_id', $currentVersion->tenant_id)
                ->where('calendar_id', $currentVersion->calendar_id)
                ->max('version_number') ?? 0;

            // Create child version
            $newVersion = ScheduleVersion::withoutGlobalScopes()->create([
                'tenant_id' => $currentVersion->tenant_id,
                'calendar_id' => $currentVersion->calendar_id,
                'name' => "Перенос v" . ($maxVersion + 1),
                'status' => 'draft',
                'created_by' => $admin->id,
                'parent_version_id' => $currentVersion->id,
                'version_number' => $maxVersion + 1,
                'generation_params' => [
                    'source' => 'reschedule',
                    'reschedule_request_id' => $request->id,
                ],
            ]);

            // Copy all assignments to new version
            $assignments = ScheduleAssignment::withoutGlobalScopes()
                ->where('schedule_version_id', $currentVersion->id)
                ->get();

            foreach ($assignments as $existing) {
                $newData = [
                    'tenant_id' => $existing->tenant_id,
                    'schedule_version_id' => $newVersion->id,
                    'activity_id' => $existing->activity_id,
                    'day_of_week' => $existing->day_of_week,
                    'slot_index' => $existing->slot_index,
                    'parity' => $existing->parity,
                    'room_id' => $existing->room_id,
                    'locked' => $existing->locked,
                    'source' => $existing->source,
                ];

                // Move the target assignment to proposed slot
                if ($existing->id === $request->assignment_id) {
                    $newData['day_of_week'] = $request->proposed_day_of_week;
                    $newData['slot_index'] = $request->proposed_slot_index;
                    $newData['parity'] = $request->proposed_parity;
                    if ($request->proposed_room_id) {
                        $newData['room_id'] = $request->proposed_room_id;
                    }
                    $newData['source'] = 'manual';
                }

                ScheduleAssignment::withoutGlobalScopes()->create($newData);
            }

            // Publish new version
            $this->scheduleService->publish($newVersion);

            // Update request status
            $request->update([
                'status' => RescheduleRequest::STATUS_APPROVED,
                'admin_comment' => $comment,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            Log::info("Reschedule approved", [
                'request_id' => $request->id,
                'new_version_id' => $newVersion->id,
            ]);

            return $newVersion;
        });
    }

    /**
     * Reject a reschedule request
     */
    public function reject(RescheduleRequest $request, User $admin, ?string $comment = null): void
    {
        $request->update([
            'status' => RescheduleRequest::STATUS_REJECTED,
            'admin_comment' => $comment,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }
}
