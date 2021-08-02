<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Scope as SearchScope;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as EloquentScope;
use Laravel\Scout\Builder as ScoutBuilder;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Provider
 */
class ProviderTest extends TestCase {
    /**
     * @covers ::registerScopes
     */
    public function testRegisterScopes(): void {
        // Model
        $model = new class() extends Model {
            use Searchable;

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        // Scope
        $scope = new class() implements EloquentScope, SearchScope {
            public function apply(EloquentBuilder $builder, Model $model): void {
                // empty
            }

            public function applyForSearch(SearchBuilder $builder, Model $model): void {
                $builder->where('test', 'test');
            }
        };

        $model->addGlobalScope($scope);

        // Test
        /** @var \App\Services\Search\Builder $builder */
        $builder = $model->search();

        $this->assertEquals(['test' => 'test'], $builder->wheres);
    }

    /**
     * @covers ::registerBindings
     */
    public function testRegisterBindings(): void {
        $this->assertInstanceOf(SearchBuilder::class, $this->app->make(ScoutBuilder::class, [
            'query' => '',
            'model' => new class() extends Model {
                // empty
            },
        ]));

        $this->assertInstanceOf(SearchRequestFactory::class, $this->app->make(SearchRequestFactoryInterface::class));
    }
}
