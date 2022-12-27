<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Commands\FulltextIndexesRebuild;
use App\Services\Search\Commands\IndexesRebuild;
use App\Services\Search\Elastic\ClientBuilder;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\GraphQL\ModelConverter;
use App\Services\Search\GraphQL\ScoutFieldResolver;
use App\Services\Search\Listeners\ElasticDisconnected;
use App\Services\Search\Listeners\IndexExpiredListener;
use App\Services\Search\Queue\Jobs\AssetsIndexer;
use App\Services\Search\Queue\Jobs\CustomersIndexer;
use App\Services\Search\Queue\Jobs\DocumentsIndexer;
use App\Services\Search\Queue\Tasks\ModelsIndex;
use App\Utils\Providers\EventsProvider;
use App\Utils\Providers\ServiceServiceProvider;
use Elastic\Client\ClientBuilderInterface;
use Elastic\Elasticsearch\Client;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Illuminate\Contracts\Container\Container;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Scout;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Scout\FieldResolver;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

class Provider extends ServiceServiceProvider {
    use ProviderWithSchedule;

    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        IndexExpiredListener::class,
        ElasticDisconnected::class,
    ];

    // <editor-fold desc="Register">
    // =========================================================================
    public function register(): void {
        parent::register();

        $this->registerJobs();
        $this->registerBindings();
        $this->registerGraphqlTypes();
        $this->registerElasticClient();
    }

    protected function registerJobs(): void {
        Scout::$makeSearchableJob   = ModelsIndex::class;
        Scout::$removeFromSearchJob = ModelsIndex::class;
    }

    protected function registerBindings(): void {
        $this->app->singleton(Indexer::class);
        $this->app->bind(ScoutBuilder::class, SearchBuilder::class);
        $this->app->bind(SearchParametersFactoryInterface::class, SearchRequestFactory::class);
        $this->app->bind(FieldResolver::class, ScoutFieldResolver::class);
    }

    protected function registerGraphqlTypes(): void {
        $this->app->afterResolving(
            TypeRegistry::class,
            static function (TypeRegistry $types, Container $container): void {
                // Test schema?
                if ($container->make(SchemaSourceProvider::class) instanceof TestSchemaProvider) {
                    return;
                }

                // Convert
                $converter = $container->make(ModelConverter::class);
                $service   = $container->make(Service::class);

                foreach ($service->getSearchableModels() as $model) {
                    foreach ($converter->toInputObjectTypes($model) as $type) {
                        $types->register($type);
                    }
                }
            },
        );
    }

    protected function registerElasticClient(): void {
        $this->app->singleton(ClientBuilderInterface::class, ClientBuilder::class);
        $this->app->bind(Client::class, static function (Container $container): Client {
            return $container->make(ClientBuilderInterface::class)->default()->setAsync(false);
        });
    }
    // </editor-fold>

    // <editor-fold desc="Boot">
    // =========================================================================
    public function boot(): void {
        $this->commands(
            IndexesRebuild::class,
            FulltextIndexesRebuild::class,
        );
        $this->bootSchedule(
            AssetsIndexer::class,
            CustomersIndexer::class,
            DocumentsIndexer::class,
        );
    }
    // </editor-fold>
}
