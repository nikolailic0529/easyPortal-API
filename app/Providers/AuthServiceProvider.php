<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Note;
use App\Models\User;
use App\Policies\NotePolicy;
use App\Policies\UserPolicy;
use Config\Constants;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;

use function strtr;

class AuthServiceProvider extends ServiceProvider {
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {
        $this->bootPolicies();
        $this->bootPasswordResetUrl();
    }

    protected function bootPolicies(): void {
        $this->app->afterResolving(
            GateContract::class,
            static function (GateContract $gate): void {
                $gate->policy(User::class, UserPolicy::class);
                $gate->policy(Note::class, NotePolicy::class);
            },
        );
    }

    protected function bootPasswordResetUrl(): void {
        $config    = $this->app->make(Repository::class);
        $generator = $this->app->make(UrlGenerator::class);

        ResetPassword::createUrlUsing(static function (User $user, string $token) use ($config, $generator) {
            return $generator->to(strtr(
                $config->get('ep.client.password_reset_uri') ?? Constants::EP_CLIENT_PASSWORD_RESET_URI,
                [
                    '{email}' => $user->getEmailForPasswordReset(),
                    '{token}' => $token,
                ],
            ));
        });
    }
}
