<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\RequestChange;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\RequestAssetChange
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class RequestAssetChangeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     * @param array<string,mixed> $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($orgFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settingsFactory);

        Mail::fake();

        if ($prepare) {
            $prepare($this, $organization, $user);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$organization) {
                $organization = $this->setOrganization(Organization::factory()->create());
            }

            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);

            Asset::factory()->create([
                'reseller_id' => $reseller->getKey(),
                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
            ]);
        }

        $input = $input
            ?: [
                'from'     => 'user@example.com',
                'subject'  => 'subject',
                'message'  => 'message',
                'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
            ];

        $map  = [];
        $file = [];

        if (isset($input['files'])) {
            foreach ((array) $input['files'] as $index => $item) {
                $file[$index] = $item;
                $map[$index]  = ["variables.input.files.{$index}"];
            }

            $input['files'] = null;
        }

        $query      = /** @lang GraphQL */
            'mutation RequestAssetChange($input: RequestAssetChangeInput!) {
            requestAssetChange(input:$input) {
                created {
                    subject
                    message
                    from
                    to
                    cc
                    bcc
                    user_id
                    files {
                        name
                    }
                }
            }
        }';
        $operations = [
            'operationName' => 'RequestAssetChange',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];
        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(RequestChange::class);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare  = static function (TestCase $test, ?Organization $organization): void {
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);

            Asset::factory()->create([
                'reseller_id' => $reseller->getKey(),
                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
            ]);
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('requestAssetChange'),
            new OrgUserDataProvider(
                'requestAssetChange',
                [
                    'requests-asset-change',
                ],
                'fd421bad-069f-491c-ad5f-5841aa9a9dee',
            ),
            new ArrayDataProvider([
                'ok'              => [
                    new GraphQLSuccess('requestAssetChange', RequestAssetChange::class, [
                        'created' => [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                            'subject' => 'subject',
                            'message' => 'change request',
                            'from'    => 'user@example.com',
                            'to'      => ['test@example.com'],
                            'cc'      => ['cc@example.com'],
                            'bcc'     => ['bcc@example.com'],
                            'files'   => [
                                [
                                    'name' => 'documents.csv',
                                ],
                            ],
                        ],
                    ]),
                    $settings,
                    $prepare,
                    [
                        'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'subject'  => 'subject',
                        'message'  => 'change request',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                        'files'    => [
                            UploadedFile::fake()->create('documents.csv', 100),
                        ],
                    ],
                ],
                'Invalid asset'   => [
                    new GraphQLError('requestAssetChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        'subject'  => 'subject',
                        'message'  => 'change request',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                    ],
                ],
                'Invalid subject' => [
                    new GraphQLError('requestAssetChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => '',
                        'message'  => 'change request',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                        'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
                'Invalid message' => [
                    new GraphQLError('requestAssetChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => 'subject',
                        'message'  => '',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['bcc@example.com'],
                        'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
                'Invalid cc'      => [
                    new GraphQLError('requestAssetChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => 'subject',
                        'message'  => 'message',
                        'from'     => 'user@example.com',
                        'cc'       => ['wrong'],
                        'bcc'      => ['bcc@example.com'],
                        'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
                'Invalid bcc'     => [
                    new GraphQLError('requestAssetChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject'  => 'subject',
                        'message'  => 'message',
                        'from'     => 'user@example.com',
                        'cc'       => ['cc@example.com'],
                        'bcc'      => ['wrong'],
                        'asset_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
