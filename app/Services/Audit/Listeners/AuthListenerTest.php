<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Auditor;
use App\Services\Audit\Contexts\Auth\SignIn;
use App\Services\Audit\Contexts\Auth\SignInFailed;
use App\Services\Audit\Contexts\Auth\SignOut;
use App\Services\Audit\Contexts\Context;
use App\Services\Audit\Enums\Action;
use App\Services\Keycloak\Auth\UserProvider;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\OrganizationProvider;
use Closure;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;
use Tests\WithOrganization;

/**
 * @internal
 * @covers \App\Services\Audit\Listeners\AuthListener
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class AuthListenerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testSubscribe(): void {
        $user          = Mockery::mock(Authenticatable::class);
        $signIn        = new Login(__METHOD__, $user, false);
        $signOut       = new Logout(__METHOD__, $user);
        $signFailed    = new Failed(__METHOD__, $user, []);
        $passwordReset = new PasswordReset($user);

        $this->override(
            AuthListener::class,
            static function (MockInterface $mock) use ($signIn, $signOut, $signFailed, $passwordReset): void {
                $mock
                    ->shouldReceive('__invoke')
                    ->with($signIn)
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->with($signOut)
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->with($signFailed)
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->with($passwordReset)
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->never();
            },
        );

        $dispatcher = $this->app->make(Dispatcher::class);

        $dispatcher->dispatch($signIn);
        $dispatcher->dispatch($signOut);
        $dispatcher->dispatch($signFailed);
        $dispatcher->dispatch($passwordReset);
    }

    public function testInvokeUnsupported(): void {
        self::expectException(LogicException::class);

        $listener = $this->app->make(AuthListener::class);

        $listener(new stdClass());
    }

    public function testInvokeLoginEvent(): void {
        $user  = User::factory()->make(['type' => UserType::keycloak()]);
        $event = new Login(__FUNCTION__, $user, $this->faker->boolean());

        $this->override(Auditor::class, static function (MockInterface $mock) use ($user, $event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::authSignedIn(),
                    null,
                    Mockery::on(static function (Context $context) use ($event): bool {
                        return $context instanceof SignIn
                            && $context->jsonSerialize() === (new SignIn(
                                $event->guard,
                                $event->remember,
                            ))->jsonSerialize();
                    }),
                    $user,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(AuthListener::class);

        $listener($event);
    }

    public function testInvokeLogoutEventUser(): void {
        $user  = User::factory()->make(['type' => UserType::local()]);
        $event = new Logout(__FUNCTION__, $user);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($user, $event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    null,
                    Action::authSignedOut(),
                    null,
                    Mockery::on(static function (Context $context) use ($event): bool {
                        return $context instanceof SignOut
                            && $context->jsonSerialize() === (new SignOut(
                                $event->guard,
                            ))->jsonSerialize();
                    }),
                    $user,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(AuthListener::class);

        $listener($event);
    }

    public function testInvokeLogoutEventGuest(): void {
        /** @phpstan-ignore-next-line `$user` can be `null` */
        $event = new Logout(__FUNCTION__, null);

        $this->override(Auditor::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('create')
                ->never();
        });

        $listener = $this->app->make(AuthListener::class);

        $listener($event);
    }

    public function testInvokeFailedEvent(): void {
        $org   = Organization::factory()->make();
        $user  = User::factory()->make(['type' => UserType::local()]);
        $event = new Failed(__FUNCTION__, $user, [
            UserProvider::CREDENTIAL_ORGANIZATION => $org,
            UserProvider::CREDENTIAL_EMAIL        => $user->email,
        ]);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($org, $user, $event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $org,
                    Action::authFailed(),
                    null,
                    Mockery::on(static function (Context $context) use ($user, $event): bool {
                        return $context instanceof SignInFailed
                            && $context->jsonSerialize() === (new SignInFailed(
                                $event->guard,
                                $user->email,
                            ))->jsonSerialize();
                    }),
                    $user,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(AuthListener::class);

        $listener($event);
    }

    public function testInvokePasswordResetEvent(): void {
        $user  = User::factory()->make(['type' => UserType::local()]);
        $event = new PasswordReset($user);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($user): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::authPasswordReset(),
                    null,
                    null,
                    $user,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(AuthListener::class);

        $listener($event);
    }

    /**
     * @dataProvider dataProviderGetUserOrganization
     *
     * @param Closure(static, ?Organization, ?Authenticatable): mixed $expected
     * @param OrganizationFactory                                     $orgFactory
     * @param Closure(static, ?Organization): Authenticatable         $userFactory
     */
    public function testGetUserOrganization(Closure $expected, mixed $orgFactory, Closure $userFactory): void {
        $org      = $this->setOrganization($orgFactory);
        $user     = $userFactory($this, $org);
        $expected = $expected($this, $org, $user);
        $auditor  = Mockery::mock(Auditor::class);
        $provider = $this->app->make(CurrentOrganization::class);
        $actual   = new class($provider, $auditor) extends AuthListener {
            public function getUserOrganization(Authenticatable $user): OrganizationProvider|Organization|string|null {
                return parent::getUserOrganization($user);
            }
        };

        self::assertEquals($expected, $actual->getUserOrganization($user));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{
     *      Closure(static, ?Organization, ?Authenticatable): mixed,
     *      OrganizationFactory,
     *      Closure(static): Authenticatable
     *      }>
     */
    public function dataProviderGetUserOrganization(): array {
        return [
            'not `User`'                                 => [
                static function (TestCase $test): mixed {
                    return $test->app->make(CurrentOrganization::class);
                },
                static function (): Organization {
                    return Organization::factory()->make();
                },
                static function (): Authenticatable {
                    return Mockery::mock(Authenticatable::class);
                },
            ],
            'User (local)'                               => [
                static function (): mixed {
                    return null;
                },
                static function (): Organization {
                    return Organization::factory()->make();
                },
                static function (): Authenticatable {
                    return User::factory()->make([
                        'type' => UserType::local(),
                    ]);
                },
            ],
            'User (not local with `organization_id`)'    => [
                static function (): mixed {
                    return '701d06c8-3a4c-4de5-8f40-daf50e910024';
                },
                static function (): Organization {
                    return Organization::factory()->make();
                },
                static function (): Authenticatable {
                    return User::factory()->make([
                        'type'            => UserType::keycloak(),
                        'organization_id' => '701d06c8-3a4c-4de5-8f40-daf50e910024',
                    ]);
                },
            ],
            'User (not local without `organization_id`)' => [
                static function (TestCase $test): mixed {
                    return $test->app->make(CurrentOrganization::class);
                },
                static function (): Organization {
                    return Organization::factory()->make();
                },
                static function (): Authenticatable {
                    return User::factory()->make([
                        'type'            => UserType::keycloak(),
                        'organization_id' => null,
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
