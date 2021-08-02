<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\QuoteRequestDuration;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class QuoteRequestDurationsTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $durationsFactory = null,
        Closure $translationsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        if ($durationsFactory) {
            $durationsFactory($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                quoteRequestDurations {
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
            new OrganizationDataProvider('quoteRequestDurations'),
            new AuthUserDataProvider('quoteRequestDurations'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('quoteRequestDurations', self::class, [
                        [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'Translated (locale)',
                        ],
                    ]),
                    static function (): void {
                        QuoteRequestDuration::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => '1-5 years',
                        ]);
                    },
                    static function (TestCase $test, string $locale): array {
                        $model = (new QuoteRequestDuration())->getMorphClass();
                        $key   = "models.{$model}.439a0a06-d98a-41f0-b8e5-4e5722518e00.name";
                        return [
                            $locale => [
                                $key => 'Translated (locale)',
                            ],
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
