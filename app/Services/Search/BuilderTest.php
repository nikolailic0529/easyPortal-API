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
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        $builder->whereMetadata('test', 'value');

        $this->assertEquals([
            Builder::METADATA.'.test.keyword' => 'value',
        ], $builder->wheres);
    }
}
