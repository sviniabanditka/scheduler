<?php

namespace App\Filament\Resources\TeacherPreferenceRuleResource\Pages;

use App\Filament\Resources\TeacherPreferenceRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherPreferenceRules extends ListRecords
{
    protected static string $resource = TeacherPreferenceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
