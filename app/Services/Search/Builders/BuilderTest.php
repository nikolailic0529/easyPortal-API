<?php declare(strict_types = 1);

namespace App\Services\Search\Builders;

use App\Models\Customer;
use App\Services\Search\Configuration;
use App\Services\Search\Contracts\Scope;
use App\Services\Search\Contracts\Scope as SearchScope;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;
use InvalidArgumentException;
use Mockery;
use stdClass;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;
use Tests\WithSearch;

use function count;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Builders\Builder
 */
class BuilderTest extends TestCase {
    use WithoutOrganizationScope;
    use WithSearch;

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void {
        $model = new class() extends Model {
            // empty
        };
        $scope = new class() implements EloquentScope, SearchScope {
            public function apply(EloquentBuilder $builder, Model $model): void {
                // empty
            }

            public function applyForSearch(Builder $builder, Model $model): void {
                $builder->where('test', 'value');
            }
        };

        $model->addGlobalScope($scope);

        $builder = $this->app->make(Builder::class, [
            'query' => '*',
            'model' => $model,
        ]);

        self::assertEquals('*', $builder->query);
        self::assertEquals(['test' => 'value'], $builder->wheres);
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

        self::assertSame($builder, $builder->whereMetadata('test', 'value'));
        self::assertEquals([
            Configuration::getMetadataName('test') => 'value',
        ], $builder->wheres);
    }

    /**
     * @covers ::whereMetadataIn
     */
    public function testWhereMetadataIn(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        self::assertSame($builder, $builder->whereMetadataIn('test', ['a', 'b', 'c']));
        self::assertEquals([
            Configuration::getMetadataName('test') => ['a', 'b', 'c'],
        ], $builder->whereIns);
    }

    /**
     * @covers ::whereMetadataNotIn
     */
    public function testWhereMetadataNotIn(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        self::assertSame($builder, $builder->whereMetadataNotIn('test', ['a', 'b', 'c']));
        self::assertEquals([
            Configuration::getMetadataName('test') => ['a', 'b', 'c'],
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

        self::assertSame($builder, $builder->whereNotIn('test', ['a', 'b', 'c']));
        self::assertEquals([
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

        self::assertSame($builder, $builder->whereNot('test', 'value'));
        self::assertEquals([
            'test' => 'value',
        ], $builder->whereNots);
    }

    /**
     * @covers ::offset
     */
    public function testOffset(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        self::assertSame($builder, $builder->offset(123));
        self::assertEquals(123, $builder->offset);
    }

    /**
     * @covers ::applyScope
     */
    public function testApplyScope(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);
        $scope   = Mockery::mock(SearchScope::class);
        $scope
            ->shouldReceive('applyForSearch')
            ->with($builder, $builder->model)
            ->once()
            ->andReturns();

        $builder->applyScope($scope);
    }

    /**
     * @covers ::applyScope
     */
    public function testApplyScopeNotAScope(): void {
        $builder = $this->app->make(Builder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);

        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `%s` must be instance of `%s`, `%s` given.',
            '$scope',
            Scope::class,
            stdClass::class,
        )));

        $builder->applyScope(stdClass::class);
    }

    /**
     * @covers ::count
     */
    public function testCount(): void {
        $models = $this->makeSearchable(Customer::factory()->count(3)->make());

        self::assertEquals(count($models), Customer::search('*')->count());
    }
}
