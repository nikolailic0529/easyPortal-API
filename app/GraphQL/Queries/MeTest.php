<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
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
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $settings = null,
    ): void {
        $this->setUser($userFactory, $this->setTenant($tenantFactory));
        $this->setSettings($settings);

        $this
            ->graphQL(/** @lang GraphQL */ '{
                me {
                    id
                    family_name
                    given_name
                    locale
                    root
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
                'guest is allowed'           => [
                    new GraphQLSuccess('me', null),
                    static function (): ?User {
                        return null;
                    },
                ],
                'user is allowed (not root)' => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment('root', false)),
                    static function (): ?User {
                        return User::factory()->make();
                    },
                ],
                'user is allowed (root)'     => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment('root', true)),
                    static function (): ?User {
                        return User::factory()->make([
                            'id' => '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        ]);
                    },
                    [
                        'ep.root_users' => [
                            '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
