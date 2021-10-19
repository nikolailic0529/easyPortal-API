<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use App\Models\User;
use App\Services\DataLoader\Jobs\AssetSync;
use App\Services\DataLoader\Jobs\DocumentSync;
use Closure;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

use function __;
use function array_filter;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Document\Sync
 */
class SyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     * @param array<mixed>        $input
     */
    public function testInvoke(
        Response $expected,
        string $query,
        string $queryType,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        array $input = [],
        Closure $prepare = null,
    ): void {
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $id           = $this->faker->uuid;

        $this->setSettings($settings);

        if ($prepare) {
            $prepare($this, $organization, $user, $input);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$organization) {
                $organization = $this->setOrganization(Organization::factory()->create());
            }


            $type     = Type::factory()->create();
            $reseller = Reseller::factory()->create([
                'id' => $organization ? $organization->getKey() : $this->faker->uuid,
            ]);

            if (!$settings) {
                $this->setSettings([
                    "ep.{$query}_types" => [$type->getKey()],
                ]);
            }

            Document::factory()->create([
                'id'          => $id,
                'type_id'     => $type,
                'reseller_id' => $reseller,
            ]);
        }

        Queue::fake();

        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<GRAPHQL
                mutation sync(\$input: [{$queryType}!]!) {
                    {$query} {
                        sync(input: \$input) {
                            result
                        }
                    }
                }
                GRAPHQL,
                [
                    'input' => $input ?: [['id' => $id]],
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Queue::assertPushed(DocumentSync::class, count($input));
            Queue::assertPushed(AssetSync::class, count(array_filter($input, static function (array $call): bool {
                return ($call['assets'] ?? false) === true;
            })));

            foreach ($input as $call) {
                unset($call['assets']);

                Queue::assertPushed(DocumentSync::class, static function (DocumentSync $job) use ($call): bool {
                    $params = [
                        'id' => $job->getDocumentId(),
                    ];
                    $params = array_filter($params, static fn(mixed $value): bool => $value !== null);
                    $pushed = $call === $params;

                    return $pushed;
                });
            }
        } else {
            Queue::assertNothingPushed();
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $type    = '9ddfa0cb-307a-476b-b859-32ab4e0ad5b5';
        $factory = static function (
            TestCase $test,
            Organization $organization,
            User $user,
            array $input,
        ) use (
            $type,
        ): void {
            $type     = Type::factory()->create(['id' => $type]);
            $asset    = Asset::factory()->create();
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);

            foreach ($input as $call) {
                Document::factory()
                    ->hasEntries(1, [
                        'asset_id' => $asset,
                    ])
                    ->create([
                        'id'          => $call['id'],
                        'type_id'     => $type,
                        'reseller_id' => $reseller,
                    ]);
            }
        };

        return (new MergeDataProvider([
            'contract' => new CompositeDataProvider(
                new ArrayDataProvider([
                    [
                        new UnknownValue(),
                        'contract',
                        'ContractSyncInput',
                    ],
                ]),
                new RootOrganizationDataProvider('contract'),
                new RootUserDataProvider('contract'),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess(
                            'contract',
                            new JsonFragmentSchema('sync', self::class),
                            new JsonFragment('sync', [
                                'result' => true,
                            ]),
                        ),
                        [
                            'ep.contract_types' => [$type],
                        ],
                        [
                            [
                                'id' => '90398f16-036f-4e6b-af90-06e19614c57c',
                            ],
                            [
                                'id' => '0a0354b5-16e8-4173-acb3-69ef10304681',
                            ],
                        ],
                        $factory,
                    ],
                    'ok with assets' => [
                        new GraphQLSuccess(
                            'contract',
                            new JsonFragmentSchema('sync', self::class),
                            new JsonFragment('sync', [
                                'result' => true,
                            ]),
                        ),
                        [
                            'ep.contract_types' => [$type],
                        ],
                        [
                            [
                                'id'     => '4f820bae-79a5-4558-b90c-d8d7060688b8',
                                'assets' => true,
                            ],
                        ],
                        $factory,
                    ],
                    'invalid type'   => [
                        new GraphQLError('contract', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.contract_types' => ['90398f16-036f-4e6b-af90-06e19614c57c'],
                        ],
                        [
                            [
                                'id' => '29c0298a-14c8-4ca4-b7da-ef7ff71d19ae',
                            ],
                        ],
                        $factory,
                    ],
                    'not found'      => [
                        new GraphQLError('contract', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.contract_types' => [$type],
                        ],
                        [
                            [
                                'id' => 'ef317ed7-fc3c-439d-9679-a6248bf6e69c',
                            ],
                        ],
                        static function (): void {
                            // empty
                        },
                    ],
                ]),
            ),
            'quote'    => new CompositeDataProvider(
                new ArrayDataProvider([
                    [
                        new UnknownValue(),
                        'quote',
                        'QuoteSyncInput',
                    ],
                ]),
                new RootOrganizationDataProvider('quote'),
                new RootUserDataProvider('quote'),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess(
                            'quote',
                            new JsonFragmentSchema('sync', self::class),
                            new JsonFragment('sync', [
                                'result' => true,
                            ]),
                        ),
                        [
                            'ep.quote_types' => [$type],
                        ],
                        [
                            [
                                'id' => '90398f16-036f-4e6b-af90-06e19614c57c',
                            ],
                            [
                                'id' => '0a0354b5-16e8-4173-acb3-69ef10304681',
                            ],
                        ],
                        $factory,
                    ],
                    'ok with assets' => [
                        new GraphQLSuccess(
                            'quote',
                            new JsonFragmentSchema('sync', self::class),
                            new JsonFragment('sync', [
                                'result' => true,
                            ]),
                        ),
                        [
                            'ep.quote_types' => [$type],
                        ],
                        [
                            [
                                'id'     => '4f820bae-79a5-4558-b90c-d8d7060688b8',
                                'assets' => true,
                            ],
                        ],
                        $factory,
                    ],
                    'invalid type'   => [
                        new GraphQLError('quote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.quote_types' => ['0a0354b5-16e8-4173-acb3-69ef10304681'],
                        ],
                        [
                            [
                                'id' => '2181735f-42b6-41bf-a069-47a88883b239',
                            ],
                        ],
                        $factory,
                    ],
                    'not found'      => [
                        new GraphQLError('quote', static function (): array {
                            return [__('errors.validation_failed')];
                        }),
                        [
                            'ep.quote_types' => [$type],
                        ],
                        [
                            [
                                'id' => '8b79a366-f9c2-4eb1-b8e5-5423bc333f96',
                            ],
                        ],
                        static function (): void {
                            // empty
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
