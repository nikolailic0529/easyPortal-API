<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Asset;
use App\Models\Data\Type;
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
     * @param OrganizationFactory                           $orgFactory
     * @param UserFactory                                   $userFactory
     * @param SettingsFactory                               $settingsFactory
     * @param Closure(static, ?Organization, ?User): string $prepare
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
        $id   = $this->faker->uuid();

        $this->setSettings($settingsFactory);

        if ($prepare) {
            $id = $prepare($this, $org, $user);
        } elseif ($org) {
            $type = Type::factory()->create();

            if (!$settingsFactory) {
                $this->setSettings([
                    "ep.{$query}_types" => [$type->getKey()],
                ]);
            }

            Document::factory()->ownedBy($org)->create([
                'id'      => $id,
                'type_id' => $type,
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
                    'id' => $id,
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
        $type    = '9ddfa0cb-307a-476b-b859-32ab4e0ad5b5';
        $factory = static function (TestCase $test, ?Organization $org) use ($type): string {
            $type     = Type::factory()->create(['id' => $type]);
            $asset    = Asset::factory()->ownedBy($org)->create();
            $document = Document::factory()
                ->ownedBy($org)
                ->hasEntries(1, [
                    'asset_id' => $asset,
                ])
                ->create([
                    'type_id' => $type,
                ]);

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
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [$type],
                        ],
                        $factory,
                    ],
                    'invalid type' => [
                        new GraphQLError('contract', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => ['90398f16-036f-4e6b-af90-06e19614c57c'],
                        ],
                        static function (self $test, Organization $org): string {
                            return Document::factory()->ownedBy($org)->create()->getKey();
                        },
                    ],
                    'not found'    => [
                        new GraphQLError('contract', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [$type],
                        ],
                        static function (self $test): string {
                            return $test->faker->uuid();
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
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => [$type],
                        ],
                        $factory,
                    ],
                    'invalid type' => [
                        new GraphQLError('quote', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => ['0a0354b5-16e8-4173-acb3-69ef10304681'],
                        ],
                        static function (self $test, Organization $org): string {
                            return Document::factory()->ownedBy($org)->create()->getKey();
                        },
                    ],
                    'not found'    => [
                        new GraphQLError('quote', static function (): Throwable {
                            return new ObjectNotFound((new Document())->getMorphClass());
                        }),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => [$type],
                        ],
                        static function (self $test): string {
                            return $test->faker->uuid();
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
