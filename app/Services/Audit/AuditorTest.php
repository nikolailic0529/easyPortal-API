<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Models\Audits\Audit;
use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use Closure;
use Illuminate\Auth\AuthManager;
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
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::modelCreated(), $changeRequest);
        });

        $changeRequest->save();
    }

    /**
     * @covers ::create
     *
     */
    public function testUpdated(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->create([
            'subject' => 'old',
        ]);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::modelUpdated(), $changeRequest);
        });

        $changeRequest->subject = 'new';
        $changeRequest->save();
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
                ->with(Action::modelDeleted(), $changeRequest);
        });

        $changeRequest->delete();
    }
    /**
     * @covers ::create
     * @dataProvider dataProviderCreate
     *
     * @param array<string,mixed> $settings
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
        $this->assertDatabaseHas((new Audit())->getTable(), $expected);
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            'model.created' => [
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
            'model.updated' => [
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
            'model.deleted' => [
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
            'auth.signIn'   => [
                static function (TestCase $test, Organization $organization, User $user): array {
                    return [
                        'object_id'       => $user->getKey(),
                        'object_type'     => $user->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'action'          => Action::authSignedIn(),
                    ];
                },
                static function (TestCase $test, Organization $organization, User $user): void {
                    $auth = $test->app->make(AuthManager::class);
                    $auth->login($user);
                },
            ],
            'auth.signOut'  => [
                static function (TestCase $test, Organization $organization, User $user): array {
                    return [
                        'object_id'       => $user->getKey(),
                        'object_type'     => $user->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'action'          => Action::authSignedOut(),
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
