<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\Models\Customer;
use App\Models\Status;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Customers\CustomerStatuses
 */
class CustomerStatusesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $statusesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($statusesFactory) {
            $statusesFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                customerStatuses(where: {customers: { where: {}, count: {lessThan: 1} }}) {
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
                new GraphQLSuccess('customerStatuses', CustomerStatuses::class, [
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
                    $model      = (new Status())->getMorphClass();

                    $translator->addLines([
                        "models.{$model}.6f19ef5f-5963-437e-a798-29296db08d59.name" => 'Translated (locale)',
                    ], $locale);

                    $translator->addLines([
                        "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name" => 'Translated (fallback)',
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
                        'name'        => 'Should be translated',
                        'object_type' => (new Customer())->getMorphClass(),
                    ]);
                    Status::factory()->create([
                        'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                        'name'        => 'Should be translated via fallback',
                        'object_type' => (new Customer())->getMorphClass(),
                    ]);
                    Status::factory()->create([
                        'name'        => 'Wrong object_type',
                        'object_type' => 'unknown',
                    ]);
                },
            ],
        ]);

        return (new MergeDataProvider([
            'customers-view' => new CompositeDataProvider(
                new AnyOrganizationDataProvider('customerStatuses'),
                new OrganizationUserDataProvider('customerStatuses', [
                    'customers-view',
                ]),
                $provider,
            ),
        ]))->getData();
    }
    // </editor-fold>
}
