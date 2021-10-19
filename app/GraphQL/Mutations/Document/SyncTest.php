<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\Models\Asset;
use App\Models\Document;
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
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

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
     * @param array<mixed> $input
     */
    public function testInvoke(
        Response $expected,
        string $query,
        string $type,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $input = [],
    ): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        Queue::fake();

        foreach ($input as $document) {
            Document::factory()
                ->hasEntries(1, [
                    'asset_id' => Asset::factory()->create(),
                ])
                ->create([
                    'id' => $document['id'],
                ]);
        }

        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<GRAPHQL
                mutation sync(\$input: [{$type}!]!) {
                    {$query} {
                        sync(input: \$input) {
                            result
                        }
                    }
                }
                GRAPHQL,
                [
                    'input' => $input ?: [['id' => '79b91f78-c244-4e95-a99d-bf8b15255591']],
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
                            [
                                'id' => '90398f16-036f-4e6b-af90-06e19614c57c',
                            ],
                            [
                                'id' => '0a0354b5-16e8-4173-acb3-69ef10304681',
                            ],
                        ],
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
                            [
                                'id'     => '4f820bae-79a5-4558-b90c-d8d7060688b8',
                                'assets' => true,
                            ],
                        ],
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
                            [
                                'id' => '90398f16-036f-4e6b-af90-06e19614c57c',
                            ],
                            [
                                'id' => '0a0354b5-16e8-4173-acb3-69ef10304681',
                            ],
                        ],
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
                            [
                                'id'     => '4f820bae-79a5-4558-b90c-d8d7060688b8',
                                'assets' => true,
                            ],
                        ],
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
