<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\GraphQL\ModelConverter;
use App\Services\Search\GraphQL\ScoutSortColumnResolver;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Contracts\ScoutColumnResolver;
use Nuwave\Lighthouse\Schema\TypeRegistry;

class Provider extends ServiceProvider {
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>>
     */
    protected static array $searchable = [
        Asset::class,
        Customer::class,
        Document::class,
    ];

    public function register(): void {
        parent::register();

        $this->registerBindings();
        $this->registerGraphqlTypes();
    }

    protected function registerBindings(): void {
        $this->app->bind(Builder::class, SearchBuilder::class);
        $this->app->bind(SearchRequestFactoryInterface::class, SearchRequestFactory::class);
        $this->app->bind(ScoutColumnResolver::class, ScoutSortColumnResolver::class);
    }

    protected function registerGraphqlTypes(): void {
        $this->app->afterResolving(
            TypeRegistry::class,
            static function (TypeRegistry $types, Container $container): void {
                $converter = $container->make(ModelConverter::class);

                foreach (static::$searchable as $model) {
                    foreach ($converter->toInputObjectTypes($model) as $type) {
                        $types->register($type);
                    }
                }
            },
        );
    }
}
