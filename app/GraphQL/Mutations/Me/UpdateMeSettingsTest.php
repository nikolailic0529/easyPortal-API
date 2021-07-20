<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

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
 * @coversDefaultClass \App\GraphQL\Mutations\Me\UpdateMeSettings
 */
class UpdateMeSettingsTest extends TestCase {
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
        $this->graphQL(/** @lang GraphQL */ 'mutation updateMeSettings($input: UpdateMeSettingsInput!) {
            updateMeSettings(input:$input) {
                result
            }
        }', ['input' => $input])
        ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $user = $user->fresh();
            array_key_exists('homepage', $input)
                ? $this->assertEquals($user->homepage, $input['homepage'])
                : $this->assertNull($user->homepage);
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
            new OrganizationDataProvider('updateMeSettings'),
            new AuthUserDataProvider('updateMeSettings'),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('updateMeSettings', UpdateMeSettings::class),
                    static function (): array {
                        return [
                            'homepage' => 'Dashboard',
                            'timezone' => 'Europe/London',
                            'locale'   => 'en_GB',
                        ];
                    },
                ],
                'nullable'                         => [
                    new GraphQLSuccess('updateMeSettings', UpdateMeSettings::class),
                    static function (): array {
                        return [];
                    },
                ],
                'invalid request/Invalid locale'   => [
                    new GraphQLError('updateMeSettings', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'locale' => 'wrong locale',
                        ];
                    },
                ],
                'invalid request/Invalid timezone' => [
                    new GraphQLError('updateMeSettings', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'timezone' => 'wrong locale',
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
