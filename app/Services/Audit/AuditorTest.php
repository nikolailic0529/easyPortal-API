<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Http\Controllers\Export\QueryExported;
use App\Models\Audits\Audit;
use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Audit\Auditor
 */
class AuditorTest extends TestCase {
    /**
     * @covers ::create
     *
     */
    public function testCreated(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->make();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $properties = [];
            foreach ($changeRequest->getAttributes() as $field => $value) {
                $properties[$field] = [
                    'value'    => $changeRequest->getAttribute($field),
                    'previous' => null,
                ];
            }
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::modelCreated(), ['properties' => $properties], $changeRequest);
        });

        $changeRequest->save();
    }

    /**
     * @covers ::create
     *
     */
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
                        Action $auditAction,
                        array $auditContext = null,
                        Model $auditModel = null,
                        Authenticatable $auditUser = null,
                    ) use (
                        $model,
                        $changes,
                    ): bool {
                        return $auditAction === Action::modelUpdated()
                            && $auditModel === $model
                            && $auditUser === null
                            && isset($auditContext['properties']['updated_at'])
                            && ($auditContext['properties']['subject'] ?? null) === $changes;
                    },
                );
        });

        $model->subject = 'new';
        $model->save();
    }

    /**
     * @covers ::create
     */
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

    /**
     * @covers ::create
     *
     */
    public function testDeleted(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->create();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::modelDeleted(), ['properties' => []], $changeRequest);
        });

        $changeRequest->delete();
    }

    /**
     * @covers ::create
     *
     */
    public function testLogin(): void {
        $user = User::factory()->create();

        $this->override(Auditor::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::authSignedIn(), ['guard' => 'test'])
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

    /**
     * @covers ::create
     *
     */
    public function testLogout(): void {
        $user = User::factory()->make();
        Auth::guard('web')->login($user);
        $this->override(Auditor::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::authSignedOut(), ['guard' => 'web']);
        });
        Auth::logout();
    }

    /**
     * @covers ::create
     *
     */
    public function testLoginFailed(): void {
        $this->override(Auditor::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::authFailed(), ['guard' => 'web']);
        });
        Auth::guard('web')->attempt(['email' => 'test@example.com', 'password' => '12345']);
    }

    /**
     * @covers ::create
     *
     */
    public function testExported(): void {
        $query = [
            'root'    => 'assets',
            'columns' => [
                [
                    'name'  => 'Name',
                    'value' => 'path.to.property',
                ],
            ],
            'query'   => 'query { asset { id } }',
        ];

        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $this->override(Auditor::class, static function (MockInterface $mock) use ($query): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::exported(), [
                    'type'  => 'csv',
                    'query' => $query,
                ]);
        });
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->dispatch(new QueryExported('csv', $query));
    }

    /**
     * @covers ::create
     *
     */
    public function testResetPassword(): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $this->override(Auditor::class, static function (MockInterface $mock) use ($user): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::authPasswordReset(), null, null, $user);
        });
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->dispatch(new PasswordReset($user));
    }

    /**
     * @covers ::create
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
