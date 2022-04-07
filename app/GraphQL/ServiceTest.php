<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\User;
use GraphQL\Type\Introspection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\Providers\Users\RootUserProvider;
use Tests\Providers\Users\UserProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderIntrospection
     *
     * @param SettingsFactory $settingsFactory
     * @param UserFactory     $userFactory
     */
    public function testIntrospection(
        Response $expected,
        mixed $settingsFactory,
        mixed $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->graphQL(Introspection::getIntrospectionQuery())
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderPlayground
     *
     * @param SettingsFactory $settingsFactory
     * @param UserFactory     $userFactory
     */
    public function testPlayground(
        Response $expected,
        mixed $settingsFactory,
        mixed $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->get('/graphql-playground')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderIntrospection(): array {
        $data = $this->dataProviderPlayground();
        $data = array_map(
            static function (array $case): array {
                $case[0] = $case[0] instanceof Ok
                    ? new GraphQLSuccess('__schema', null)
                    : new GraphQLError('__schema');

                return $case;
            },
            $data,
        );

        return $data;
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderPlayground(): array {
        $success  = new Ok();
        $failed   = new Forbidden();
        $enabled  = static function (): array {
            return [
                'app.debug' => true,
            ];
        };
        $disabled = static function (): array {
            return [
                'app.debug' => false,
            ];
        };
        $guest    = static function (): ?User {
            return null;
        };
        $user     = new UserProvider();
        $root     = new RootUserProvider();

        return (new MergeDataProvider([
            'debug on'  => new ArrayDataProvider([
                'guest' => [
                    $success,
                    $enabled,
                    $guest,
                ],
                'user'  => [
                    $success,
                    $enabled,
                    $user,
                ],
                'root'  => [
                    $success,
                    $enabled,
                    $root,
                ],
            ]),
            'debug off' => new ArrayDataProvider([
                'guest' => [
                    $failed,
                    $disabled,
                    $guest,
                ],
                'user'  => [
                    $failed,
                    $disabled,
                    $user,
                ],
                'root'  => [
                    $success,
                    $disabled,
                    $root,
                ],
            ]),
        ]))->getData();
    }
    // </editor-fold>
}
