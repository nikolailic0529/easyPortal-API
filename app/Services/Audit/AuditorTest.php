<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Models\Audits\Audit;
use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Contexts\Auth\SignIn;
use App\Services\Audit\Contexts\Auth\SignInFailed;
use App\Services\Audit\Contexts\Auth\SignOut;
use App\Services\Audit\Contexts\Context;
use App\Services\Audit\Enums\Action;
use App\Services\Audit\Listeners\AuditableListener;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Audit\Auditor
 */
class AuditorTest extends TestCase {
    public function testCreated(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->make();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $properties = [];
            foreach ($changeRequest->getAttributes() as $field => $value) {
                $properties[$field] = [
                    'value'    => $value,
                    'previous' => null,
                ];
            }
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::modelCreated(),
                    $changeRequest,
                    [
                        AuditableListener::PROPERTIES => $properties,
                    ],
                );
        });

        $changeRequest->save();
    }

    public function testUpdated(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));

        $model   = ChangeRequest::factory()->create([
            'subject'    => 'old',
            'created_at' => Date::now()->subDay(),
            'updated_at' => Date::now()->subDay(),
        ]);
        $changes = [
            'value'    => 'new',
            'previous' => 'old',
        ];

        $this->override(Auditor::class, static function (MockInterface $mock) use ($model, $changes): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->withArgs(
                    static function (
                        CurrentOrganization|Organization|null $auditOrg,
                        Action $auditAction,
                        Model $auditModel = null,
                        array $auditContext = null,
                        Authenticatable $auditUser = null,
                    ) use (
                        $model,
                        $changes,
                    ): bool {
                        return $auditOrg instanceof CurrentOrganization
                            && $auditAction === Action::modelUpdated()
                            && $auditModel === $model
                            && $auditUser === null
                            && isset($auditContext[AuditableListener::PROPERTIES]['updated_at'])
                            && ($auditContext[AuditableListener::PROPERTIES]['subject'] ?? null) === $changes;
                    },
                );
        });

        $model->subject = 'new';
        $model->save();
    }

    public function testUpdatedEmpty(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));

        $model = User::factory()->create();

        $this->override(Auditor::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('create')
                ->never();
        });

        $model->synced_at = Date::now();
        $model->save();
    }

    public function testDeleted(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->create();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::modelDeleted(),
                    $changeRequest,
                    [
                        // empty
                    ],
                );
        });

        $changeRequest->delete();
    }

    public function testLogin(): void {
        $user = User::factory()->create();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($user): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::authSignedIn(),
                    null,
                    Mockery::on(static function (Context $context): bool {
                        return $context instanceof SignIn
                            && $context->jsonSerialize() === (new SignIn('test', false))->jsonSerialize();
                    }),
                    $user,
                )
                ->andReturns();
            $mock
                ->shouldReceive('create')
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)->dispatch(
            new Login('test', $user, false),
        );
    }

    public function testLogout(): void {
        $user = User::factory()->make();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($user): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::authSignedOut(),
                    null,
                    Mockery::on(static function (Context $context): bool {
                        return $context instanceof SignOut
                            && $context->jsonSerialize() === (new SignOut('test'))->jsonSerialize();
                    }),
                    $user,
                )
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)->dispatch(
            new Logout('test', $user),
        );
    }

    public function testLoginFailed(): void {
        $this->override(Auditor::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(
                    null,
                    Action::authFailed(),
                    null,
                    Mockery::on(static function (Context $context): bool {
                        return $context instanceof SignInFailed
                            && $context->jsonSerialize() === (new SignInFailed(
                                'web',
                                'test@example.com',
                            ))->jsonSerialize();
                    }),
                    null,
                );
        });

        Auth::guard('web')->attempt([
            'email'    => 'test@example.com',
            'password' => '12345',
        ]);
    }

    public function testResetPassword(): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $this->override(Auditor::class, static function (MockInterface $mock) use ($user): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::authPasswordReset(),
                    null,
                    null,
                    $user,
                );
        });
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->dispatch(new PasswordReset($user));
    }

    /**
     * @dataProvider dataProviderCreate
     */
    public function testCreate(Closure $expectedFactory, Closure $prepare = null): void {
        $user         = User::factory()->create([
            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
        ]);
        $organization = Organization::factory()->create([
            'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
        ]);
        $this->setUser($user, $this->setOrganization($organization));
        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        $expected = $expectedFactory($this, $organization, $user);
        self::assertDatabaseHas((new Audit())->getTable(), $expected, Auditor::CONNECTION);
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            'model.created'  => [
                static function (): array {
                    return [
                        'object_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'object_type'     => (new ChangeRequest())->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'action'          => Action::modelCreated(),
                    ];
                },
                static function (): void {
                    $changeRequest = ChangeRequest::factory()->make([
                        'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    ]);
                    $changeRequest->save();
                },
            ],
            'model.updated'  => [
                static function (): array {
                    return [
                        'object_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'object_type'     => (new ChangeRequest())->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'action'          => Action::modelUpdated(),
                    ];
                },
                static function (): void {
                    $changeRequest          = ChangeRequest::factory()->create([
                        'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'subject'         => 'old',
                    ]);
                    $changeRequest->subject = 'new';
                    $changeRequest->save();
                },
            ],
            'model.deleted'  => [
                static function (): array {
                    return [
                        'object_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'object_type'     => (new ChangeRequest())->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'action'          => Action::modelDeleted(),
                    ];
                },
                static function (): void {
                    $changeRequest = ChangeRequest::factory()->create([
                        'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                    ]);
                    $changeRequest->delete();
                },
            ],
            'model.restored' => [
                static function (): array {
                    return [
                        'object_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'object_type'     => (new ChangeRequest())->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'action'          => Action::modelRestored(),
                    ];
                },
                static function (): void {
                    $changeRequest = ChangeRequest::factory()->create([
                        'id'              => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'deleted_at'      => '2021-01-01 00:00:00',
                    ]);
                    $changeRequest->restore();
                },
            ],
            'auth.signIn'    => [
                static function (TestCase $test, Organization $organization, User $user): array {
                    return [
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'action'          => Action::authSignedIn(),
                        'user_id'         => $user->getKey(),
                    ];
                },
                static function (TestCase $test, Organization $organization, User $user): void {
                    $auth = $test->app->make(AuthManager::class);
                    $auth->guard('web')->login($user);
                },
            ],
            'auth.signOut'   => [
                static function (TestCase $test, Organization $organization, User $user): array {
                    return [
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'action'          => Action::authSignedOut(),
                        'user_id'         => $user->getKey(),
                    ];
                },
                static function (TestCase $test, Organization $organization, User $user): void {
                    $auth = $test->app->make(AuthManager::class);
                    $auth->logout($user);
                },
            ],
        ];
    }
    // </editor-fold>
}
