<?php

namespace App\Services;

use App\Models\ScheduleVersion;
use App\Models\SoftWeight;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScheduleGenerationService
{
    protected string $solverUrl;

    public function __construct()
    {
        $this->solverUrl = env('SOLVER_URL', 'http://solver:8081');
    }

    /**
     * Create a new schedule version and trigger generation
     */
    public function generate(
        string $tenantId,
        int $calendarId,
        int $createdBy,
        ?array $weights = null,
        int $timeoutSeconds = 420,
        ?string $name = null,
        string $algorithm = 'greedy'
    ): ScheduleVersion {
        // Get or create weights
        $softWeights = SoftWeight::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->first();

        $defaultWeights = [
            'w_windows' => $softWeights->w_windows ?? 10,
            'w_prefs' => $softWeights->w_prefs ?? 5,
            'w_balance' => $softWeights->w_balance ?? 2,
        ];

        $weights = $weights ?? $defaultWeights;

        // Find next version number
        $maxVersion = ScheduleVersion::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('calendar_id', $calendarId)
            ->max('version_number') ?? 0;

        // Create schedule version
        $version = ScheduleVersion::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'calendar_id' => $calendarId,
            'name' => $name ?? "Розклад v" . ($maxVersion + 1),
            'status' => 'draft',
            'created_by' => $createdBy,
            'version_number' => $maxVersion + 1,
            'random_seed' => random_int(1, 999999),
            'generation_params' => [
                'weights' => $weights,
                'timeout_seconds' => $timeoutSeconds,
                'algorithm' => $algorithm,
            ],
        ]);

        // Call Go solver
        try {
            $response = Http::timeout($timeoutSeconds + 30)->post("{$this->solverUrl}/api/v1/generate", [
                'tenant_id' => $tenantId,
                'calendar_id' => $calendarId,
                'schedule_id' => $version->id,
                'weights' => $weights,
                'timeout_seconds' => $timeoutSeconds,
                'algorithm' => $algorithm,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                $version->update([
                    'generation_params' => array_merge($version->generation_params ?? [], [
                        'result_status' => $result['status'] ?? 'unknown',
                        'total_violations' => $result['total_violations'] ?? 0,
                        'solve_time_ms' => $result['solve_time_ms'] ?? 0,
                        'assignment_count' => count($result['assignment_ids'] ?? []),
                    ]),
                ]);

                Log::info("Schedule generation complete", [
                    'version_id' => $version->id,
                    'status' => $result['status'] ?? 'unknown',
                    'violations' => $result['total_violations'] ?? 0,
                ]);
            } else {
                Log::error("Solver returned error", [
                    'version_id' => $version->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to call solver", [
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $version->refresh();
    }

    /**
     * Publish a schedule version (unpublish currently published one)
     */
    public function publish(ScheduleVersion $version): void
    {
        // Unpublish current published version for same calendar
        ScheduleVersion::withoutGlobalScopes()
            ->where('tenant_id', $version->tenant_id)
            ->where('calendar_id', $version->calendar_id)
            ->where('status', 'published')
            ->update(['status' => 'archived']);

        $version->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Archive a schedule version
     */
    public function archive(ScheduleVersion $version): void
    {
        $version->update(['status' => 'archived']);
    }
}
