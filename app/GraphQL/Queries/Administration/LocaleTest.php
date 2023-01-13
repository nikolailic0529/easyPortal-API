<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Services\I18n\I18n;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Administration\Locale
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class LocaleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory   $orgFactory
     * @param UserFactory           $userFactory
     * @param array<string, string> $strings
     * @param array<string, string> $defaults
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        string $locale = null,
        array $strings = null,
        array $defaults = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $locale ??= 'en_GB';

        // Mock
        if ($locale && $strings && $defaults) {
            $this->override(
                I18n::class,
                static function (MockInterface $mock) use ($locale, $strings, $defaults): void {
                    $mock
                        ->shouldReceive('getTranslations')
                        ->with($locale)
                        ->once()
                        ->andReturn($strings);
                    $mock
                        ->shouldReceive('getDefaultTranslations')
                        ->with($locale)
                        ->once()
                        ->andReturn($defaults);
                },
            );
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query test($locale: String!){
                    locale(name: $locale) {
                        name
                        translations {
                            key
                            value
                            default
                        }
                    }
                }
                GRAPHQL,
                [
                    'locale' => $locale,
                ],
            )
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
            new AuthOrgRootDataProvider('locale'),
            new AuthRootDataProvider('locale'),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('locale', [
                        'name'         => 'en_GB',
                        'translations' => [
                            [
                                'key'     => 'a',
                                'value'   => 'a',
                                'default' => 'default-a',
                            ],
                        ],
                    ]),
                    'en_GB',
                    [
                        'a' => 'a',
                    ],
                    [
                        'a' => 'default-a',
                    ],
                ],
                'invalid locale' => [
                    new GraphQLValidationError('locale', static function (): array {
                        return [
                            'name' => [
                                trans('validation.locale'),
                            ],
                        ];
                    }),
                    'invalid',
                    null,
                    null,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
