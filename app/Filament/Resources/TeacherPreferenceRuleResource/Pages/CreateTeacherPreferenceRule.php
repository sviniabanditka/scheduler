<?php

namespace App\Filament\Resources\TeacherPreferenceRuleResource\Pages;

use App\Filament\Resources\TeacherPreferenceRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherPreferenceRule extends CreateRecord
{
    protected static string $resource = TeacherPreferenceRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user->isTeacher() && $user->teacher_id) {
            $data['teacher_id'] = $user->teacher_id;
        }
        return $data;
    }
}
