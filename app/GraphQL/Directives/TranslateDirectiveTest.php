<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use App\GraphQL\Contracts\Translatable;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use stdClass;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithTenant;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\TranslateDirective
 */
class TranslateDirectiveTest extends TestCase {
    use WithGraphQLSchema;
    use WithTenant;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     */
    public function testResolveField(Response $expected, object $object): void {
        $this->useRootTenant();
        $this->mockResolver($object);

        $this
            ->graphQLSchema(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                type Query {
                    model: Model! @mock
                }

                type Model {
                    property: String @translate
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    model {
                        property
                    }
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
            'not Translatable' => [
                new GraphQLSuccess('model', null, [
                    'property' => 'abc',
                ]),
                new class() extends stdClass {
                    public string $property = 'abc';
                },
            ],
            'Translatable'     => [
                new GraphQLSuccess('model', null, [
                    'property' => 'translated',
                ]),
                new class() extends stdClass implements Translatable {
                    public string $property = 'abc';

                    public function getTranslatedProperty(string $property): string {
                        return 'translated';
                    }
                },
            ],
        ];
    }
    // </editor-fold>
}
