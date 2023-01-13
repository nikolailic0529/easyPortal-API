<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\Services\I18n\I18n;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Locale\Reset
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ResetTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        string $locale = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $locale ??= 'en_GB';

        // Mock
        if ($expected instanceof GraphQLSuccess && $locale) {
            $this->override(
                I18n::class,
                static function (MockInterface $mock) use ($locale): void {
                    $mock
                        ->shouldReceive('resetTranslations')
                        ->with($locale)
                        ->once()
                        ->andReturn(true);
                },
            );
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($locale: String!){
                    locale(name: $locale) {
                        reset {
                            result
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
                    new GraphQLSuccess('locale', new JsonFragment('reset.result', true)),
                    'en_GB',
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
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
