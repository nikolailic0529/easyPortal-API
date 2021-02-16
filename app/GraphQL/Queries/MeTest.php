<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\DataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\NotFoundResponse;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
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
     * @dataProvider dataProviderInfo
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
    public function dataProviderInfo(): array {
        return (new CompositeDataProvider(
            $this->getTenantDataProvider(),
            new ArrayDataProvider([
                'guest is allowed' => [
                    new OkResponse(new GraphQLSuccess('me', null)),
                    static function (): ?User {
                        return null;
                    },
                ],
                'user is allowed'  => [
                    new OkResponse(new GraphQLSuccess('me', Me::class)),
                    static function (): ?User {
                        return User::factory()->make();
                    },
                ],
            ]),
        ))->getData();
    }

    protected function getTenantDataProvider(): DataProvider {
        return new ArrayDataProvider([
            'no tenant' => [
                new ExpectedFinal(new NotFoundResponse()),
                static function (): ?Organization {
                    return null;
                },
            ],
            'tenant'    => [
                new Unknown(),
                static function (self $test): ?Organization {
                    return Organization::factory()->create([
                        'subdomain' => $test->faker->word,
                    ]);
                },
            ],
        ]);
    }
    // </editor-fold>
}
