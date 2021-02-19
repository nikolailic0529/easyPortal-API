<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Me
 */
class MeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(Response $expected, Closure $tenantFactory, Closure $userFactory = null): void {
        $this->setTenant($tenantFactory);
        $this->setUser($userFactory);

        $this
            ->graphQL(/** @lang GraphQL */ '{
                me {
                    id,
                    family_name,
                    given_name
                }
            }')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new ArrayDataProvider([
                'guest is allowed' => [
                    new GraphQLSuccess('me', null),
                    static function (): ?User {
                        return null;
                    },
                ],
                'user is allowed'  => [
                    new GraphQLSuccess('me', Me::class),
                    static function (): ?User {
                        return User::factory()->make();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
