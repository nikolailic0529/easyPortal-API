<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Storages\ClientSettings;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function is_array;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\DeleteClientSettings
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteClientSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param Response|array{response:Response,content:array<mixed>} $expected
     * @param OrganizationFactory                                    $orgFactory
     * @param UserFactory                                            $userFactory
     * @param array<mixed>                                           $content
     * @param array<string>                                          $names
     */
    public function testInvoke(
        Response|array $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $content = [],
        array $names = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Mock
        $storage = null;

        if ($content) {
            $storage = $this->app->make(ClientSettings::class);

            $storage->save($content);

            $this->app->bind(ClientSettings::class, static function () use ($storage) {
                return $storage;
            });
        }

        // Test
        $expectedResponse = is_array($expected) ? $expected['response'] : $expected;
        $expectedContent  = is_array($expected) ? $expected['content'] : null;

        $this
            ->graphQL(
                /** @lang GraphQL */
                '
                mutation deleteClientSettings($names: [String!]!) {
                    deleteClientSettings(input: {names: $names}) {
                        deleted
                    }
                }',
                [
                    'names' => $names,
                ],
            )
            ->assertThat($expectedResponse);

        if ($expectedContent) {
            self::assertNotNull($expectedResponse);
            self::assertEquals($expectedContent, $storage->load());
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('deleteClientSettings'),
            new AuthRootDataProvider('deleteClientSettings'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'deleteClientSettings',
                            [
                                'deleted' => ['a', 'b'],
                            ],
                        ),
                        'content'  => [
                            [
                                'name'  => 'c',
                                'value' => 'c',
                            ],
                        ],
                    ],
                    [
                        [
                            'name'  => 'a',
                            'value' => 'sdfsdf',
                        ],
                        [
                            'name'  => 'b',
                            'value' => 'c',
                        ],
                        [
                            'name'  => 'c',
                            'value' => 'c',
                        ],
                    ],
                    ['a', 'b', 'a'],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
