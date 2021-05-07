<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;

use function addslashes;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Root
 */
class RootTest extends TestCase {
    use WithGraphQLSchema;
    use WithOrganization;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateTypeExtension
     * @covers ::manipulateTypeDefinition
     * @covers ::manipulateFieldDefinition
     * @covers ::addRequirements
     */
    public function testDirective(): void {
        $this->assertGraphQLSchemaEquals(
            $this->getTestData()->content('~expected.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::resolveField
     * @covers ::isRoot
     *
     * @dataProvider dataProviderResolveField
     *
     * @param array<string,mixed> $settings
     */
    public function testResolveField(Response $expected, array $settings, Closure $userFactory): void {
        $this->useRootOrganization();
        $this->setUser($userFactory);
        $this->setSettings($settings);

        $resolver = addslashes(RootDirectiveTest_Resolver::class);

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @root @field(resolver: "{$resolver}")
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    value
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderResolveField(): array {
        return [
            'no settings - no user' => [
                new GraphQLUnauthenticated('value'),
                [],
                static function () {
                    return null;
                },
            ],
            'user is not root'      => [
                new GraphQLUnauthorized('value'),
                [
                    'ep.root_users' => [
                        '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        'f470ecc9-1394-4f95-bfa2-435307f9c4f3',
                    ],
                ],
                static function () {
                    return User::factory()->make([
                        'id' => 'da83c04b-5273-418f-ad78-134324cc1c01',
                    ]);
                },
            ],
            'user is root'          => [
                new GraphQLSuccess('value', null),
                [
                    'ep.root_users' => [
                        '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        'f470ecc9-1394-4f95-bfa2-435307f9c4f3',
                    ],
                ],
                static function () {
                    return User::factory()->make([
                        'id' => 'f470ecc9-1394-4f95-bfa2-435307f9c4f3',
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class RootDirectiveTest_Resolver {
    public function __invoke(): string {
        return __FUNCTION__;
    }
}
