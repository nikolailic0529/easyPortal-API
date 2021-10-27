<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Document;
use App\Models\Type;
use Closure;
use Illuminate\Translation\Translator;
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
 * @coversDefaultClass \App\GraphQL\Queries\Contracts\ContractTypes
 */
class ContractTypesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        array $settings = [],
        Closure $contactFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setSettings($settings);

        if ($contactFactory) {
            $contactFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                contractTypes(where: {contracts: { where: {}, count: {lessThan: 1} } }) {
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
            'ok/from contract types'         => [
                new GraphQLSuccess('contractTypes', ContractTypes::class, [
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
                static function (TestCase $test): string {
                    $translator = $test->app()->make(Translator::class);
                    $fallback   = $translator->getFallback();
                    $locale     = $test->app()->getLocale();
                    $model      = (new Type())->getMorphClass();

                    $translator->addLines([
                        "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                    ], $locale);

                    $translator->addLines([
                        "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
                    ], $fallback);

                    return $locale;
                },
                [
                    'ep.contract_types' => [
                        'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        '6f19ef5f-5963-437e-a798-29296db08d59',
                        'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                    ],
                ],
                static function (TestCase $test): void {
                    Type::factory()->create([
                        'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'name'        => 'No translation',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Type::factory()->create([
                        'id'          => '6f19ef5f-5963-437e-a798-29296db08d59',
                        'key'         => 'translated',
                        'name'        => 'Should be translated',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Type::factory()->create([
                        'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        'key'         => 'translated-fallback',
                        'name'        => 'Should be translated via fallback',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Type::factory()->create([
                        'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                        'key'         => 'key',
                        'name'        => 'Not In config',
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                    Type::factory()->create([
                        'name'        => 'Wrong object_type',
                        'object_type' => 'unknown',
                    ]);
                },
            ],
            'ok/empty contract types config' => [
                new GraphQLSuccess('contractTypes', ContractTypes::class, [
                    // empty
                ]),
                static function (TestCase $test): string {
                    return $test->app->getLocale();
                },
                [
                    'ep.contract_types' => [],
                ],
                static function (TestCase $test): void {
                    Type::factory()->create([
                        'object_type' => (new Document())->getMorphClass(),
                    ]);
                },
            ],
        ]);

        return (new MergeDataProvider([
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('contractTypes'),
                new OrganizationUserDataProvider('contractTypes', [
                    'customers-view',
                ]),
                $provider,
            ),
            'contracts-view' => new CompositeDataProvider(
                new OrganizationDataProvider('contractTypes'),
                new OrganizationUserDataProvider('contractTypes', [
                    'contracts-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}