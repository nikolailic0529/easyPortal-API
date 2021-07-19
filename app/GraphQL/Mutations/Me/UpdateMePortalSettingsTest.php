<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Language;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Me\UpdateMePortalSettings
 */
class UpdateMePortalSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $input = [];
        if ($prepare) {
            $input = $prepare($this);
        }
        // Test
        $this->graphQL(/** @lang GraphQL */ 'mutation updateMePortalSettings($input: UpdateMePortalSettingsInput!) {
            updateMePortalSettings(input:$input) {
                result
            }
        }', ['input' => $input])
        ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $user = $user->fresh();
            array_key_exists('homepage', $input)
                ? $this->assertEquals($user->homepage, $input['homepage'])
                : $this->assertNull($user->homepage);
            array_key_exists('language_id', $input)
                ? $this->assertEquals($user->language_id, $input['language_id'])
                : $this->assertNull($user->language_id);
            array_key_exists('locale', $input)
                ? $this->assertEquals($user->locale, $input['locale'])
                : $this->assertNull($user->locale);
            array_key_exists('timezone', $input)
                ? $this->assertEquals($user->timezone, $input['timezone'])
                : $this->assertNull($user->timezone);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateMePortalSettings'),
            new AuthUserDataProvider('updateMePortalSettings'),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('updateMePortalSettings', UpdateMePortalSettings::class),
                    static function (): array {
                        $language = Language::factory()->create();
                        return [
                            'homepage'    => 'Dashboard',
                            'timezone'    => 'Europe/London',
                            'language_id' => $language->getKey(),
                            'locale'      => 'en_GB',
                        ];
                    },
                ],
                'nullable'                         => [
                    new GraphQLSuccess('updateMePortalSettings', UpdateMePortalSettings::class),
                    static function (): array {
                        return [];
                    },
                ],
                'invalid request/Invalid locale'   => [
                    new GraphQLError('updateMePortalSettings', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'locale' => 'wrong locale',
                        ];
                    },
                ],
                'invalid request/Invalid timezone' => [
                    new GraphQLError('updateMePortalSettings', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'timezone' => 'wrong locale',
                        ];
                    },
                ],
                'invalid request/Invalid language' => [
                    new GraphQLError('updateMePortalSettings', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'language_id' => $test->faker->uuid,
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
