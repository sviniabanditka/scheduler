<?php

namespace App\Providers;

use App\Models\RescheduleRequest;
use App\Models\TeacherPreferenceRule;
use App\Models\User;
use App\Policies\RescheduleRequestPolicy;
use App\Policies\TeacherPreferenceRulePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        TeacherPreferenceRule::class => TeacherPreferenceRulePolicy::class,
        RescheduleRequest::class => RescheduleRequestPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
