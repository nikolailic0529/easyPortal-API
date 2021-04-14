<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Storages\ClientSettings;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateClientSettings
 */
class UpdateClientSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param \LastDragon_ru\LaraASP\Testing\Constraints\Response\Response|array{
     *      response:\LastDragon_ru\LaraASP\Testing\Constraints\Response\Response,
     *      content:array<mixed>
     *      } $expected
     * @param array<mixed> $content
     * @param array{name:string,value:string} $settings
     */
    public function testInvoke(
        Response|array $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $content = [],
        array $settings = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

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
            /** @lang GraphQL */ '
                mutation updateClientSettings($settings: [UpdateClientSettingsInput!]!) {
                    updateClientSettings(input: $settings) {
                        updated {
                            name
                            value
                        }
                    }
                }',
                [
                    'settings' => $settings,
                ],
            )
            ->assertThat($expectedResponse);

        if ($expectedContent) {
            $this->assertNotNull($expectedResponse);
            $this->assertEquals($expectedContent, $storage->load());
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
            new TenantDataProvider(),
            new RootDataProvider('updateClientSettings'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'updateClientSettings',
                            UpdateClientSettings::class,
                            [
                                'updated' => [
                                    [
                                        'name'  => 'a',
                                        'value' => 'a',
                                    ],
                                    [
                                        'name'  => 'b',
                                        'value' => 'b',
                                    ],
                                ],
                            ],
                        ),
                        'content'  => [
                            [
                                'name'  => 'a',
                                'value' => 'a',
                            ],
                            [
                                'name'  => 'b',
                                'value' => 'b',
                            ],
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
                    [
                        [
                            'name'  => 'a',
                            'value' => 'a',
                        ],
                        [
                            'name'  => 'b',
                            'value' => 'b',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
