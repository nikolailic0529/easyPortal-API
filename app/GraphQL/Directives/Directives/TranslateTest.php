<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\Services\I18n\Contracts\Translatable;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use stdClass;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Translate
 */
class TranslateTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderResolveField
     */
    public function testResolveField(Response $expected, object $object): void {
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
                new GraphQLSuccess('model', [
                    'property' => 'abc',
                ]),
                new class() extends stdClass {
                    public string $property = 'abc';
                },
            ],
            'Translatable'     => [
                new GraphQLSuccess('model', [
                    'property' => 'translated',
                ]),
                new class() extends stdClass implements Translatable {
                    public string $property = 'abc';

                    public function getTranslatedProperty(string $property): string {
                        return 'translated';
                    }

                    /**
                     * @inheritDoc
                     */
                    public function getDefaultTranslations(): array {
                        return [];
                    }
                },
            ],
        ];
    }
    // </editor-fold>
}
