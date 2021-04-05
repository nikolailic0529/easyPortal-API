<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateApplicationSettings
 */
class UpdateApplicationSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     *  @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $settings = [],
        bool $addToRoot = false,
    ): void {
        // Prepare

        // So it won't save on real settings
        Storage::fake();

        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($addToRoot && $user) {
            $config = $this->app->make(Repository::class);
            $config->set('easyportal.root_user_id', $user->id);
        }
        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateApplicationSettings(
                $input: [UpdateApplicationSettingsInput!]!) {
                    updateApplicationSettings(input:$input){
                        settings {
                            name
                            value
                        }
                    }
            }', [ 'input' => $settings ])
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
            new UserDataProvider('updateApplicationSettings'),
            new ArrayDataProvider([
                'ok'           => [
                    new GraphQLSuccess('updateApplicationSettings', UpdateApplicationSettings::class, [
                        'settings' => [
                            [
                                'name'  => 'key1',
                                'value' => 'value1',
                            ],
                        ],
                    ]),
                    [
                        [
                            'name'  => 'key1',
                            'value' => 'value1',
                        ],
                    ],
                    true,
                ],
                'unauthorized' => [
                    new GraphQLError('updateApplicationSettings', static function (): array {
                        return ['Unauthorized.'];
                    }),
                    [
                        [
                            'name'  => 'key1',
                            'value' => 'value1',
                        ],
                    ],
                    false,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
