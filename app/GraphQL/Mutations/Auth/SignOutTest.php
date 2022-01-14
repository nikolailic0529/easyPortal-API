<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\KeyCloak\KeyCloak;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Auth\SignOut
 */
class SignOutTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(Response $expected, Closure $orgFactory, Closure $userFactory = null): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Mock
        if ($expected instanceof GraphQLSuccess) {
            $this->override(KeyCloak::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('signOut')
                    ->once()
                    ->andReturn('http://example.com/');
            });
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation {
                    auth {
                        signOut {
                            result
                            url
                        }
                    }
                }
                GRAPHQL,
            )
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
            new OrganizationDataProvider('auth'),
            new UserDataProvider('auth'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragmentSchema('signOut', self::class),
                        new JsonFragment('signOut', [
                            'result' => true,
                            'url'    => 'http://example.com/',
                        ]),
                    ),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
