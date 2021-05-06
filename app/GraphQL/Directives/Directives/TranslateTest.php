<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\GraphQL\Contracts\Translatable;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use stdClass;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Translate
 */
class TranslateTest extends TestCase {
    use WithGraphQLSchema;
    use WithOrganization;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     */
    public function testResolveField(Response $expected, object $object): void {
        $this->useRootOrganization();
        $this->mockResolver($object);

        $this
            ->useGraphQLSchema(
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
