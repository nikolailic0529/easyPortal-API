<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison;

use App\GraphQL\Extensions\LaraAsp\SearchBy\Metadata;
use Closure;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Mockery\MockInterface;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Extensions\LaraAsp\SearchBy\Operators\Comparison\EndsWith
 */
class EndsWithTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::apply
     *
     * @dataProvider dataProviderApply
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param array<string,mixed>                          $settings
     */
    public function testApply(
        array $expected,
        Closure $builderFactory,
        array $settings,
        string $property,
        bool $fulltext,
        mixed $value,
    ): void {
        $this->setSettings($settings);
        $this->override(Metadata::class, static function (MockInterface $mock) use ($property, $fulltext): void {
            $mock
                ->shouldReceive('isFulltextIndexExists')
                ->with(Mockery::any(), $property)
                ->once()
                ->andReturn($fulltext);
        });

        $operator = $this->app->make(EndsWith::class);
        $builder  = $builderFactory($this);
        $builder  = $operator->apply($builder, $property, $value);

        self::assertDatabaseQueryEquals($expected, $builder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderApply(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'with fulltext'                                          => [
                    [
                        'query'    => <<<'SQL'
                            select *
                            from `tmp`
                            where (
                                `property` LIKE ? ESCAPE '!' and MATCH(property) AGAINST (?)
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
                    'property',
                    true,
                    'a%b',
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
                    'property',
                    false,
                    'a%b',
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
                    'property',
                    true,
                    'abc',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
