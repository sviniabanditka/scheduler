<?php

namespace App\Jobs;

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

    public int $timeout = 600; // 10 minutes max

    public function __construct(
        public string $tenantId,
        public int $calendarId,
        public int $createdBy,
        public ?array $weights = null,
        public int $timeoutSeconds = 420,
        public ?string $name = null,
    ) {
    }

    public function handle(ScheduleGenerationService $service): void
    {
        Log::info("Starting schedule generation job", [
            'tenant_id' => $this->tenantId,
            'calendar_id' => $this->calendarId,
        ]);

        $service->generate(
            $this->tenantId,
            $this->calendarId,
            $this->createdBy,
            $this->weights,
            $this->timeoutSeconds,
            $this->name,
        );
    }
}
