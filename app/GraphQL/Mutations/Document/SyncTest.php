<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Asset;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Services\DataLoader\Queue\Tasks\DocumentSync;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;
use Throwable;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Document\Sync
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class SyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param SettingsFactory                                      $settingsFactory
     * @param Closure(static, ?Organization, ?User): Document|null $prepare
     */
    public function testInvoke(
        Response $expected,
        string $query,
        string $queryType,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
    ): void {
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $key  = $this->faker->uuid();

        $this->setSettings($settingsFactory);

        if ($prepare) {
            $key = $prepare($this, $org, $user)->getKey();
        } elseif ($org) {
            Document::factory()->ownedBy($org)->create([
                'id'          => $key,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => true,
            ]);
        } else {
            // empty
        }

        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<GRAPHQL
                mutation sync(\$id: ID!) {
                    {$query}(id: \$id) {
                        sync {
                            result
                            assets
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $key,
                ],
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
        $override = static function (TestCase $test, Document $document): string {
            $test->override(
                DocumentSync::class,
                static function (MockInterface $mock) use ($document): void {
                    $mock->makePartial();
                    $mock
                        ->shouldReceive('init')
                        ->withArgs(static function (Document $actual) use ($document): bool {
                            return $document->getKey() === $actual->getKey();
                        })
                        ->once()
                        ->andReturnSelf();
                    $mock
                        ->shouldReceive('__invoke')
                        ->once()
                        ->andReturn([
                            'result' => true,
                            'assets' => true,
                        ]);
                },
            );

            return $document->getKey();
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
                new AuthOrgDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-sync',
                ]),
                new ArrayDataProvider([
                    'ok'           => [
                        new GraphQLSuccess(
                            'contract',
                            new JsonFragment('sync', [
                                'result' => true,
                                'assets' => true,
                            ]),
                        ),
                        [
                            // empty
                        ],
                        static function (TestCase $test, ?Organization $org) use ($override): Document {
                            $asset    = Asset::factory()->ownedBy($org)->create();
                            $document = Document::factory()
                                ->ownedBy($org)
                                ->hasEntries(1, [
                                    'asset_id' => $asset,
                                ])
                                ->create([
                                    'is_hidden'   => false,
                                    'is_contract' => true,
                                    'is_quote'    => false,
                                ]);

                            $override($test, $document);

                            return $document;
                        },
                    ],
                    'invalid type' => [
                        new GraphQLError('contract', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            // empty
                        ],
                        static function (self $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => false,
                                'is_quote'    => true,
                            ]);
                        },
                    ],
                    'not found'    => [
                        new GraphQLError('contract', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            // empty
                        ],
                        static function (): Document {
                            return Document::factory()->make();
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
                new AuthOrgDataProvider('quote'),
                new OrgUserDataProvider('quote', [
                    'quotes-sync',
                ]),
                new ArrayDataProvider([
                    'ok'           => [
                        new GraphQLSuccess(
                            'quote',
                            new JsonFragment('sync', [
                                'result' => true,
                                'assets' => true,
                            ]),
                        ),
                        [
                            // empty
                        ],
                        static function (TestCase $test, ?Organization $org) use ($override): Document {
                            $asset    = Asset::factory()->ownedBy($org)->create();
                            $document = Document::factory()
                                ->ownedBy($org)
                                ->hasEntries(1, [
                                    'asset_id' => $asset,
                                ])
                                ->create([
                                    'is_hidden'   => false,
                                    'is_contract' => false,
                                    'is_quote'    => true,
                                ]);

                            $override($test, $document);

                            return $document;
                        },
                    ],
                    'invalid type' => [
                        new GraphQLError('quote', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            // empty
                        ],
                        static function (self $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                    'not found'    => [
                        new GraphQLError('quote', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            // empty
                        ],
                        static function (): Document {
                            return Document::factory()->make();
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
