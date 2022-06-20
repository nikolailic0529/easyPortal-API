<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Document;
use App\Models\Status;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Quotes\QuoteStatuses
 */
class QuoteStatusesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string, mixed>|null $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        Closure $translationsFactory = null,
        Closure $statusesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setSettings($settings);
        $this->setTranslations($translationsFactory);

        if ($statusesFactory) {
            $statusesFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                quoteStatuses(where: { quotes: { where: {}, count: {lessThan: 1} }}) {
                    id
                    name
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
        $provider = new ArrayDataProvider([
            'ok' => [
                new GraphQLSuccess('quoteStatuses', QuoteStatuses::class, [
                    [
                        'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                        'name' => 'Translated (locale)',
                    ],
                    [
                        'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        'name' => 'Translated (fallback)',
                    ],
                    [
                        'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'name' => 'No translation',
                    ],
                ]),
                [
                    'ep.document_statuses_hidden' => [
                        '1110a787-7a07-49ad-a863-56aa092d8134',
                    ],
                ],
                static function (TestCase $test, string $locale): array {
                    $model = (new Status())->getMorphClass();

                    return [
                        $locale => [
                            "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                            "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
                        ],
                    ];
                },
                static function (TestCase $test): void {
                    Status::factory()->create([
                        'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'name'        => 'No translation',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Status::factory()->create([
                        'id'          => '6f19ef5f-5963-437e-a798-29296db08d59',
                        'key'         => 'translated',
                        'name'        => 'Should be translated',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Status::factory()->create([
                        'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        'key'         => 'translated-fallback',
                        'name'        => 'Should be translated via fallback',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Status::factory()->create([
                        'name'        => 'Wrong object_type',
                        'object_type' => 'unknown',
                    ]);
                    Status::factory()->create([
                        'id'          => '1110a787-7a07-49ad-a863-56aa092d8134',
                        'key'         => 'ignored',
                        'name'        => 'Should be ignored',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                },
            ],
        ]);

        return (new MergeDataProvider([
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('quoteStatuses'),
                new OrganizationUserDataProvider('quoteStatuses', [
                    'customers-view',
                ]),
                $provider,
            ),
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('quoteStatuses'),
                new OrganizationUserDataProvider('quoteStatuses', [
                    'quotes-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}
