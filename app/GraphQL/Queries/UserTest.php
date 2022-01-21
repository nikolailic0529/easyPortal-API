<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Enums\UserOrganizationStatus;
use App\Models\Invitation;
use App\Models\OrganizationUser;
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
    public function testStatus(UserOrganizationStatus $expected, Closure $organizationUserFactory): void {
        $user   = $organizationUserFactory($this);
        $query  = $this->app->make(User::class);
        $actual = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($user, $query): UserOrganizationStatus {
                return $query->status($user);
            },
        );

        $this->assertSame($expected, $actual);
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
                UserOrganizationStatus::inactive(),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => false,
                    ]);
                },
            ],
            'enabled + not invited'                  => [
                UserOrganizationStatus::active(),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => true,
                    ]);
                },
            ],
            'enabled + invited + no invitation'      => [
                UserOrganizationStatus::expired(),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => true,
                        'invited' => true,
                    ]);
                },
            ],
            'enabled + invited + invitation'         => [
                UserOrganizationStatus::invited(),
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
                UserOrganizationStatus::expired(),
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
