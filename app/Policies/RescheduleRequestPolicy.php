<?php

namespace App\Policies;

use App\Models\RescheduleRequest;
use App\Models\User;

class RescheduleRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPlanner() || $user->isTeacher();
    }

    public function view(User $user, RescheduleRequest $request): bool
    {
        if ($user->isAdmin() || $user->isPlanner()) return true;
        return $user->isTeacher() && $user->teacher_id === $request->teacher_id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, RescheduleRequest $request): bool
    {
        // Only admins/planners can approve/reject
        if ($user->isAdmin() || $user->isPlanner()) return true;
        // Teachers can edit only their own pending requests
        return $user->isTeacher() && $user->teacher_id === $request->teacher_id && $request->isPending();
    }

    public function delete(User $user, RescheduleRequest $request): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher_id === $request->teacher_id && $request->isPending();
    }
}
