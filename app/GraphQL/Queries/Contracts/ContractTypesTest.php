<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Data\Type;
use App\Models\Document;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Contracts\ContractTypes
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ContractTypesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $localeFactory = null,
        mixed $settingsFactory = null,
        Closure $contactFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setSettings($settingsFactory);

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
                new GraphQLSuccess('contractTypes', [
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
                new GraphQLSuccess('contractTypes', [
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
            'contracts-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('contractTypes'),
                new OrgUserDataProvider('contractTypes', [
                    'contracts-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}
