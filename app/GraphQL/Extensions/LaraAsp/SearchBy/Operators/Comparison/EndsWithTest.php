<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison;

use App\GraphQL\Extensions\LaraAsp\SearchBy\Metadata;
use Closure;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithSettings;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\EndsWith
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
class EndsWithTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::call
     *
     * @dataProvider dataProviderCall
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param BuilderFactory                               $builderFactory
     * @param SettingsFactory                              $settingsFactory
     * @param Closure(static): Argument                    $argumentFactory
     */
    public function testCall(
        array $expected,
        mixed $builderFactory,
        mixed $settingsFactory,
        Property $property,
        bool $fulltext,
        mixed $argumentFactory,
    ): void {
        $this->setSettings($settingsFactory);

        $property = $property->getChild('operator name should be ignored');
        $argument = $argumentFactory($this);
        $builder  = $builderFactory($this);

        $this->override(
            Metadata::class,
            static function (MockInterface $mock) use ($builder, $property, $fulltext): void {
                $mock
                    ->shouldReceive('isFulltextIndexExists')
                    ->with(Mockery::any(), $builder->getGrammar()->wrap((string) $property->getParent()))
                    ->once()
                    ->andReturn($fulltext);
            },
        );

        $operator = $this->app->make(EndsWith::class);
        $search   = Mockery::mock(Handler::class);
        $builder  = $operator->call($search, $builder, $property, $argument);

        self::assertDatabaseQueryEquals($expected, $builder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCall(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'with fulltext'                                          => [
                    [
                        'query'    => <<<'SQL'
                            select *
                            from `tmp`
                            where (
                                `property` LIKE ? ESCAPE '!' and MATCH(`property`) AGAINST (?)
                                )
                            SQL
                        ,
                        'bindings' => [
                            '%a!%b',
                            'a%b',
                        ],
                    ],
                    [
                        'ep.search.fulltext.ngram_token_size' => 2,
                    ],
                    new Property('property'),
                    true,
                    static function (self $test): Argument {
                        return $test->getGraphQLArgument('String!', 'a%b');
                    },
                ],
                'without fulltext'                                       => [
                    [
                        'query'    => 'select * from `tmp` where `property` LIKE ? ESCAPE \'!\'',
                        'bindings' => [
                            '%a!%b',
                        ],
                    ],
                    [
                        'ep.search.fulltext.ngram_token_size' => 2,
                    ],
                    new Property('property'),
                    false,
                    static function (self $test): Argument {
                        return $test->getGraphQLArgument('String!', 'a%b');
                    },
                ],
                'value shorter than ep.search.fulltext.ngram_token_size' => [
                    [
                        'query'    => 'select * from `tmp` where `property` LIKE ? ESCAPE \'!\'',
                        'bindings' => [
                            '%abc',
                        ],
                    ],
                    [
                        'ep.search.fulltext.ngram_token_size' => 4,
                    ],
                    new Property('property'),
                    true,
                    static function (self $test): Argument {
                        return $test->getGraphQLArgument('String!', 'abc');
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
