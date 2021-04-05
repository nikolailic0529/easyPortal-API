<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Type;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Core\Utils\ConfigMerger;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\QuoteTypes
 */
class QuoteTypesTest extends TestCase {
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $localeFactory = null,
        Closure $typesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($typesFactory) {
            $typesFactory($this);
        }

        if ($settings) {
            $config = $this->app->make(Repository::class);
            $group  = 'easyportal';

            $config->set($group, (new ConfigMerger())->merge(
                $config->get($group),
                $settings,
            ));
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                quoteTypes {
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
        $factory     = static function (): void {
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
                'name'        => 'Not In quotes config',
                'object_type' => (new Document())->getMorphClass(),
            ]);
        };
        $translation = static function (TestCase $test): string {
            $translator = $test->app()->make(Translator::class);
            $fallback   = $translator->getFallback();
            $locale     = $test->app()->getLocale();
            $model      = (new Type())->getMorphClass();
            $type       = (new Document())->getMorphClass();

            $translator->addLines([
                "models.{$model}.name.{$type}.translated" => 'Translated (locale)',
            ], $locale);

            $translator->addLines([
                "models.{$model}.name.{$type}.translated-fallback" => 'Translated (fallback)',
            ], $fallback);

            return $locale;
        };
        $objects     = [
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
        ];
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'quote_types match'                         => [
                    new GraphQLSuccess('quoteTypes', QuoteTypes::class, $objects),
                    [
                       'quote_types' => [
                            'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            '6f19ef5f-5963-437e-a798-29296db08d59',
                            'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                       ],
                    ],
                    $translation,
                    $factory,
                ],
                'no quote_types + contract_types' => [
                    new GraphQLSuccess('quoteTypes', QuoteTypes::class, $objects),
                    [
                        'contract_types' => [
                            'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                        ],
                    ],
                    $translation,
                    $factory,
                ],
                'quote_types not match'                     => [
                    new GraphQLSuccess('quoteTypes', QuoteTypes::class, [
                        // empty
                    ]),
                    [
                        'quote_types' => [
                            'f3cb1fac-b454-4f23-bbb4-f3d84a1650eg',
                        ],
                    ],
                    $translation,
                    $factory,
                ],
                'no quote_types + no contract_types'        => [
                    new GraphQLSuccess('quoteTypes', QuoteTypes::class, [
                        // empty
                    ]),
                    [
                        // empty
                    ],
                    $translation,
                    $factory,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
