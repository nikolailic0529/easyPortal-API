<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Note;
use App\Models\User;
use App\Policies\NotePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
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
        User::class => UserPolicy::class,
        Note::class => NotePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {
        $this->registerPolicies();
        $this->bootPasswordResetUrl();
    }

    protected function bootPasswordResetUrl(): void {
        $config    = $this->app->make(Repository::class);
        $generator = $this->app->make(UrlGenerator::class);

        ResetPassword::createUrlUsing(static function (User $user, string $token) use ($config, $generator) {
            return $generator->to(strtr($config->get('ep.client.password_reset_uri'), [
                '{email}' => $user->getEmailForPasswordReset(),
                '{token}' => $token,
            ]));
        });
    }
}
