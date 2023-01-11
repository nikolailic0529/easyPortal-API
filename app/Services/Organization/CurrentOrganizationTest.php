<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Organization\Events\OrganizationChanged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Organization\CurrentOrganization
 */
class CurrentOrganizationTest extends TestCase {
    public function testSet(): void {
        Event::fake(OrganizationChanged::class);

        $org      = Organization::factory()->create();
        $provider = $this->app->make(CurrentOrganization::class);
        $user     = User::factory()->create([
            'organization_id' => null,
        ]);

        OrganizationUser::factory()->create([
            'organization_id' => $org,
            'user_id'         => $user,
        ]);

        $this->setUser($user);

        self::assertTrue($provider->set($org));

        Event::assertDispatched(
            OrganizationChanged::class,
            static function (OrganizationChanged $event) use ($org): bool {
                return $event->getPrevious() === null
                    && $event->getCurrent() === $org;
            },
        );
    }

    public function testSetSame(): void {
        Event::fake(OrganizationChanged::class);

        $org      = Organization::factory()->create();
        $provider = $this->app->make(CurrentOrganization::class);
        $user     = User::factory()->create([
            'organization_id' => $org,
        ]);

        OrganizationUser::factory()->create([
            'organization_id' => $org,
            'user_id'         => $user,
        ]);

        $this->setUser($user);

        self::assertTrue($provider->set($org));

        Event::assertNothingDispatched();
    }

    public function testSetNoUser(): void {
        Event::fake(OrganizationChanged::class);

        $org      = Organization::factory()->make();
        $provider = $this->app->make(CurrentOrganization::class);

        $this->setUser(null);

        self::assertFalse($provider->set($org));

        Event::assertNothingDispatched();
    }
}
