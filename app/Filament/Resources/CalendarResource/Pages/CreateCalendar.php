<?php

namespace App\Filament\Resources\CalendarResource\Pages;

use App\Filament\Resources\CalendarResource;
use App\Models\Calendar;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendar extends CreateRecord
{
    protected static string $resource = CalendarResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Repeater stores items with UUID keys — normalize to sequential array
        if (!empty($data['slot_times'])) {
            $data['slot_times'] = array_values($data['slot_times']);
        } else {
            $data['slot_times'] = Calendar::generateDefaultSlotTimes(
                $data['slots_per_day'] ?? 6,
                $data['slot_duration_minutes'] ?? 90,
                $data['break_duration_minutes'] ?? 10,
            );
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncTimeSlots();
    }
}
