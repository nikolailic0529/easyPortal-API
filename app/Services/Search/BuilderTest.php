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

        $builder->whereMetadata('test', 'value');

        $this->assertEquals([
            "{$meta}.test.keyword" => 'value',
        ], $builder->wheres);
    }

    /**
     * @covers ::whereMetadataIn
     */
    public function testWhereMetadataIn(): void {
        $meta    = Builder::METADATA;
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $builder->whereMetadataIn('test', ['a', 'b', 'c']);

        $this->assertEquals([
            "{$meta}.test.keyword" => ['a', 'b', 'c'],
        ], $builder->whereIns);
    }

    /**
     * @covers ::whereMetadataNotIn
     */
    public function testWhereMetadataNotIn(): void {
        $meta    = Builder::METADATA;
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $builder->whereMetadataNotIn('test', ['a', 'b', 'c']);

        $this->assertEquals([
            "{$meta}.test.keyword" => ['a', 'b', 'c'],
        ], $builder->whereNotIns);
    }

    /**
     * @covers ::whereNotIn
     */
    public function testWhereNotIn(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $builder->whereNotIn('test', ['a', 'b', 'c']);

        $this->assertEquals([
            'test' => ['a', 'b', 'c'],
        ], $builder->whereNotIns);
    }

    /**
     * @covers ::whereNot
     */
    public function testWhereNot(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $builder->whereNot('test', 'value');

        $this->assertEquals([
            'test' => 'value',
        ], $builder->whereNots);
    }
}
