<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\Enums\UserType;
use App\Models\User;
use Closure;
use GraphQL\Type\Introspection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderIntrospection
     */
    public function testIntrospection(
        Response $expected,
        Closure $settingsFactory,
        Closure $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->graphQL(Introspection::getIntrospectionQuery())
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderPlayground
     */
    public function testPlayground(
        Response $expected,
        Closure $settingsFactory,
        Closure $userFactory,
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
        $user     = static function (): ?User {
            return User::factory()->create();
        };
        $root     = static function (): ?User {
            return User::factory()->create([
                'type' => UserType::local(),
            ]);
        };

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
