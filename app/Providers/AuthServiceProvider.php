<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Auth\Auth;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {
        $this->registerPolicies();
        $this->bootPermission();
    }

    protected function bootPermission(): void {
        $gate = $this->app->make(Gate::class);
        $auth = $this->app->make(Auth::class);

        $gate->before([$auth, 'gateBefore']);
        $gate->after([$auth, 'gateAfter']);
    }
}
