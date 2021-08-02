<?php declare(strict_types = 1);

namespace App\Services\Search;


use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Builder
 */
class BuilderTest extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testConstruct(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $this->assertEquals(Builder::PROPERTIES.'.\\*:123', $builder->query);
    }

    /**
     * @covers ::whereMetadata
     */
    public function testWhereMetadata(): void {
        $meta    = Builder::METADATA;
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $builder->whereMetadata('test-value', 'value');
        $builder->whereMetadata('test-array', ['a', 'b', 'c']);

        $this->assertEquals([
            "{$meta}.test-value.keyword" => 'value',
        ], $builder->wheres);

        $this->assertEquals([
            "{$meta}.test-array.keyword" => ['a', 'b', 'c'],
        ], $builder->whereIns);
    }
}
