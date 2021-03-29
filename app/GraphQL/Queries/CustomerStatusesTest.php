<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Status;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\AnyDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomerStatuses
 */
class CustomerStatusesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $statusesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($statusesFactory) {
            $statusesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                customerStatuses {
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
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new AnyDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('customerStatuses', CustomerStatuses::class, [
                        [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'No translation',
                        ],
                        [
                            'id'   => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'name' => 'Translated (locale)',
                        ],
                        [
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name' => 'Translated (fallback)',
                        ],
                    ]),
                    static function (TestCase $test): string {
                        $translator = $test->app()->make(Translator::class);
                        $fallback   = $translator->getFallback();
                        $locale     = $test->app()->getLocale();
                        $model      = (new Status())->getMorphClass();
                        $type       = (new Customer())->getMorphClass();

                        $translator->addLines([
                            "model.{$model}.{$type}.translated" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "model.{$model}.{$type}.translated-fallback" => 'Translated (fallback)',
                        ], $fallback);

                        return $locale;
                    },
                    static function (TestCase $test): void {
                        Status::factory()->create([
                            'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'        => 'No translation',
                            'object_type' => (new Customer())->getMorphClass(),
                        ]);
                        Status::factory()->create([
                            'id'          => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'key'         => 'translated',
                            'name'        => 'Should be translated',
                            'object_type' => (new Customer())->getMorphClass(),
                        ]);
                        Status::factory()->create([
                            'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'key'         => 'translated-fallback',
                            'name'        => 'Should be translated via fallback',
                            'object_type' => (new Customer())->getMorphClass(),
                        ]);
                        Status::factory()->create([
                            'name' => 'Wrong object_type',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
