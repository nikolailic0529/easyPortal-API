<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Invitation;
use App\Models\OrganizationUser;
use App\Models\Status;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Closure;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\GraphQL\Queries\User
 */
class UserTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::status
     *
     * @dataProvider dataProviderStatus
     *
     * @param \Closure(self): \App\Models\OrganizationUser $organizationUserFactory
     */
    public function testStatus(Status $expected, Closure $organizationUserFactory): void {
        $user   = $organizationUserFactory($this);
        $query  = $this->app->make(User::class);
        $actual = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($user, $query): Status {
                return $query->status($user);
            },
        );

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderStatus(): array {
        return [
            'disabled'                               => [
                (new Status())->forceFill([
                    'id'   => '347e5072-9cd8-42a7-a1be-47f329a9e3eb',
                    'key'  => 'inactive',
                    'name' => 'inactive',
                ]),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => false,
                    ]);
                },
            ],
            'enabled + not invited'                  => [
                (new Status())->forceFill([
                    'id'   => 'f482da3b-f3e9-4af3-b2ab-8e4153fa8eb1',
                    'key'  => 'active',
                    'name' => 'active',
                ]),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => true,
                    ]);
                },
            ],
            'enabled + invited + no invitation'      => [
                (new Status())->forceFill([
                    'id'   => 'c4136a8c-7cc4-4e30-8712-e47565a5e167',
                    'key'  => 'expired',
                    'name' => 'expired',
                ]),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => true,
                        'invited' => true,
                    ]);
                },
            ],
            'enabled + invited + invitation'         => [
                (new Status())->forceFill([
                    'id'   => '849deaf1-1ff4-4cd4-9c03-a1c4d9ba0402',
                    'key'  => 'invited',
                    'name' => 'invited',
                ]),
                static function (): OrganizationUser {
                    $invitation = Invitation::factory()->create([
                        'expired_at' => Date::now()->addDay(),
                    ]);

                    return OrganizationUser::factory()->make([
                        'enabled'         => true,
                        'invited'         => true,
                        'user_id'         => $invitation->user_id,
                        'organization_id' => $invitation->organization_id,
                    ]);
                },
            ],
            'enabled + invited + expired invitation' => [
                (new Status())->forceFill([
                    'id'   => 'c4136a8c-7cc4-4e30-8712-e47565a5e167',
                    'key'  => 'expired',
                    'name' => 'expired',
                ]),
                static function (): OrganizationUser {
                    $invitation = Invitation::factory()->create([
                        'expired_at' => Date::now()->subDay(),
                    ]);

                    return OrganizationUser::factory()->make([
                        'enabled'         => true,
                        'invited'         => true,
                        'user_id'         => $invitation->user_id,
                        'organization_id' => $invitation->organization_id,
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
