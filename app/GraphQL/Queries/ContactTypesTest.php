<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Contact;
use App\Models\Type;
use Closure;
use Illuminate\Translation\Translator;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\ContactTypes
 */
class ContactTypesTest extends TestCase {
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $localeFactory = null,
        Closure $contactFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($contactFactory) {
            $contactFactory($this);
        }

        if ($localeFactory) {
            $this->app->setLocale($localeFactory($this));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                contactTypes {
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
            new OrganizationDataProvider('contactTypes'),
            new AuthUserDataProvider('contactTypes'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('contactTypes', ContactTypes::class, [
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
                        $model      = (new Type())->getMorphClass();
                        $type       = (new Contact())->getMorphClass();

                        $translator->addLines([
                            "models.{$model}.name.{$type}.translated" => 'Translated (locale)',
                        ], $locale);

                        $translator->addLines([
                            "models.{$model}.name.{$type}.translated-fallback" => 'Translated (fallback)',
                        ], $fallback);

                        return $locale;
                    },
                    static function (TestCase $test): void {
                        Type::factory()->create([
                            'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'        => 'No translation',
                            'object_type' => (new Contact())->getMorphClass(),
                        ]);
                        Type::factory()->create([
                            'id'          => '6f19ef5f-5963-437e-a798-29296db08d59',
                            'key'         => 'translated',
                            'name'        => 'Should be translated',
                            'object_type' => (new Contact())->getMorphClass(),
                        ]);
                        Type::factory()->create([
                            'id'          => 'f3cb1fac-b454-4f23-bbb4-f3d84a1699ae',
                            'key'         => 'translated-fallback',
                            'name'        => 'Should be translated via fallback',
                            'object_type' => (new Contact())->getMorphClass(),
                        ]);
                        Type::factory()->create([
                            'name' => 'Wrong object_type',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
