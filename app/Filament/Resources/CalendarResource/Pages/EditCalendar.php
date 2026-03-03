<?php

namespace App\Filament\Resources\CalendarResource\Pages;

use App\Filament\Resources\CalendarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalendar extends EditRecord
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Repeater stores items with UUID keys — normalize to sequential array
        if (!empty($data['slot_times'])) {
            $data['slot_times'] = array_values($data['slot_times']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncTimeSlots();
    }
}
