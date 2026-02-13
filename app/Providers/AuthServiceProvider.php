<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
// use Illuminate\Support\ServiceProvider;

use App\Models\Attempt;
use App\Models\AuditLog;
use App\Models\Import;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Tag;
use App\Models\Test;
use App\Models\User;
use App\Policies\AttemptPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\ImportPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\ReportPolicy;
use App\Policies\TagPolicy;
use App\Policies\TestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Question::class => QuestionPolicy::class,
        Test::class => TestPolicy::class,
        Invitation::class => InvitationPolicy::class,
        Attempt::class => AttemptPolicy::class,
        User::class => UserPolicy::class,
        Import::class => ImportPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Tag::class => TagPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        // Report does not have a model, so we define a gate instead.
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate for viewing reports (uses Attempt model)
        Gate::define('view-report', [ReportPolicy::class, 'view']);
        Gate::define('export-report', [ReportPolicy::class, 'export']);

        // Gate for managing users (admin only)
        Gate::define('manage-users', function (User $user) {
            return $user->role === 'admin';
        });

        // Gate for managing organization settings (admin only)
        Gate::define('manage-organization', function (User $user) {
            return $user->role === 'admin';
        });

        // Gate for candidate to access their own attempt (token-based)
        Gate::define('access-attempt', function (User $user, Attempt $attempt) {
            // The user is a Sanctum token with tokenable_type = Attempt, tokenable_id = attempt->id
            // Check if the authenticated token belongs to this attempt.
            return $user->currentAccessToken()->tokenable_type === Attempt::class
                && $user->currentAccessToken()->tokenable_id === $attempt->id;
        });

        // Gate for candidate to update their own attempt (auto-save, submit)
        Gate::define('update-own-attempt', function (User $user, Attempt $attempt) {
            return Gate::allows('access-attempt', $attempt);
        });

        // Implicitly grant "admin" role all permissions (super admin)
        Gate::before(function ($user, $ability) {
            if ($user->role === 'admin') {
                // Admin can do anything within their organization.
                // For cross-organization checks, we still need policy logic.
                // This before gate can return true for any ability, but we need to be careful.
                // Better to rely on policies with explicit admin checks.
                // We'll not use a blanket 'before' to avoid security holes.
                // Instead, policies explicitly check for admin role.
                return null; // Let policies decide.
            }
        });
    }
}
