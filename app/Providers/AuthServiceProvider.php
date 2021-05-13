<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\User;
use App\Services\Auth\Auth;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use function strtr;

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
        $this->bootPasswordResetUrl();
    }

    protected function bootPermission(): void {
        $gate = $this->app->make(Gate::class);
        $auth = $this->app->make(Auth::class);

        $gate->before([$auth, 'gateBefore']);
        $gate->after([$auth, 'gateAfter']);
    }

    protected function bootPasswordResetUrl(): void {
        $config    = $this->app->make(Repository::class);
        $generator = $this->app->make(UrlGenerator::class);

        ResetPassword::createUrlUsing(static function (User $user, string $token) use ($config, $generator) {
            return $generator->to(strtr($config->get('ep.client.password_reset_uri'), [
                '{token}' => $token,
            ]));
        });
    }
}
