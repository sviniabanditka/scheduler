<?php

namespace App\Policies;

use App\Models\TeacherPreferenceRule;
use App\Models\User;

class TeacherPreferenceRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, TeacherPreferenceRule $rule): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher_id === $rule->teacher_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, TeacherPreferenceRule $rule): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher_id === $rule->teacher_id;
    }

    public function delete(User $user, TeacherPreferenceRule $rule): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher_id === $rule->teacher_id;
    }
}
