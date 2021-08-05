<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\QuoteRequestDuration;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
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
                    key
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
            new UserDataProvider('quoteRequestDurations'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('quoteRequestDurations', self::class, [
                        [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'Translated (locale)',
                            'key'  => 'translated',
                        ],
                        [
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'name' => 'Translated (fallback)',
                            'key'  => 'translated-fallback',
                        ],
                        [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'No translation',
                            'key'  => 'No translation',
                        ],
                    ]),
                    static function (): void {
                        QuoteRequestDuration::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            'name' => 'Should be translated',
                            'key'  => 'translated',
                        ]);
                        QuoteRequestDuration::factory()->create([
                            'id'   => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'key'  => 'translated-fallback',
                            'name' => 'Should be translated via fallback',
                        ]);
                        QuoteRequestDuration::factory()->create([
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name' => 'No translation',
                            'key'  => 'No translation',
                        ]);
                    },
                    static function (TestCase $test, string $locale): array {
                        $model = (new QuoteRequestDuration())->getMorphClass();
                        $key1  = "models.{$model}.439a0a06-d98a-41f0-b8e5-4e5722518e00.name";
                        $key2  = "models.{$model}.f3cb1fac-b454-4f23-bbb4-f3d84a1699ae.name";
                        return [
                            $locale => [
                                $key1 => 'Translated (locale)',
                                $key2 => 'Translated (fallback)',
                            ],
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
