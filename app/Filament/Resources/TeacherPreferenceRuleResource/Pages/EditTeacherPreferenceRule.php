<?php

namespace App\Filament\Resources\TeacherPreferenceRuleResource\Pages;

use App\Filament\Resources\TeacherPreferenceRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherPreferenceRule extends EditRecord
{
    protected static string $resource = TeacherPreferenceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
