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
    public function testCreate(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->make();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with(Action::created(), $changeRequest, null, $changeRequest->getAttributes());
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
                ->with(Action::updated(), $changeRequest, ['subject' => 'old'], ['subject' => 'new']);
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
                ->with(Action::deleted(), $changeRequest);
        });

        $changeRequest->delete();
    }

    /**
     * @covers ::create
     * @dataProvider dataProviderAuditRecorded
     *
     * @param array<string,mixed> $settings
     */
    public function testAuditRecorded(Closure $expectedFactory, Closure $prepare = null): void {
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
    public function dataProviderAuditRecorded(): array {
        return [
            'model.created' => [
                static function (): array {
                    return [
                        'object_id'       => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ad',
                        'object_type'     => (new ChangeRequest())->getMorphClass(),
                        'organization_id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ab',
                        'user_id'         => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699aa',
                        'action'          => Action::created(),
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
                        'action'          => Action::updated(),
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
                        'action'          => Action::deleted(),
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
                        'action'          => Action::signedIn(),
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
                        'action'          => Action::signedOut(),
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
