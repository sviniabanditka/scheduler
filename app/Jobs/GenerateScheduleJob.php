<?php

namespace App\Jobs;

use App\Models\ScheduleVersion;
use App\Services\ScheduleGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 660;

    public function __construct(
        public int $scheduleVersionId,
        public string $algorithm = 'greedy',
    ) {}

    public function handle(ScheduleGenerationService $service): void
    {
        $version = ScheduleVersion::withoutGlobalScopes()->findOrFail($this->scheduleVersionId);

        $version->update([
            'status' => 'generating',
            'generation_started_at' => now(),
        ]);

        $params = $version->generation_params ?? [];

        $service->generateForVersion(
            version: $version,
            algorithm: $this->algorithm,
            timeoutSeconds: $params['timeout_seconds'] ?? 420,
            weights: $params['weights'] ?? null,
        );

        $version->update([
            'status' => 'draft',
            'generation_finished_at' => now(),
        ]);

        Log::info("GenerateScheduleJob completed", ['version_id' => $version->id]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("GenerateScheduleJob failed", [
            'version_id' => $this->scheduleVersionId,
            'error' => $exception?->getMessage(),
        ]);

        $version = ScheduleVersion::withoutGlobalScopes()->find($this->scheduleVersionId);
        if ($version) {
            $version->update([
                'status' => 'failed',
                'generation_finished_at' => now(),
            ]);
        }
    }
}
