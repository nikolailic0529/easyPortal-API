<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Commands\IndexesRebuild;
use App\Services\Search\Elastic\SearchRequestFactory;
use App\Services\Search\GraphQL\ModelConverter;
use App\Services\Search\GraphQL\ScoutColumnResolver;
use App\Services\Search\Jobs\Cron\AssetsIndexer;
use App\Services\Search\Jobs\Cron\CustomersIndexer;
use App\Services\Search\Jobs\Cron\DocumentsIndexer;
use App\Services\Search\Jobs\Index;
use App\Services\Search\Listeners\IndexExpiredListener;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Scout;
use LastDragon_ru\LaraASP\Core\Concerns\ProviderWithCommands;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Builders\Scout\ColumnResolver;
use LastDragon_ru\LaraASP\Queue\Concerns\ProviderWithSchedule;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;
use Psr\Log\LoggerInterface;

use function is_string;

class Provider extends ServiceProvider {
    use ProviderWithCommands;
    use ProviderWithSchedule;

    // <editor-fold desc="Register">
    // =========================================================================
    public function register(): void {
        parent::register();

        $this->registerJobs();
        $this->registerBindings();
        $this->registerListeners();
        $this->registerGraphqlTypes();
        $this->registerElasticClient();
    }

    protected function registerJobs(): void {
        Scout::$makeSearchableJob   = Index::class;
        Scout::$removeFromSearchJob = Index::class;
    }

    protected function registerBindings(): void {
        $this->app->bind(ScoutBuilder::class, SearchBuilder::class);
        $this->app->bind(SearchRequestFactoryInterface::class, SearchRequestFactory::class);
        $this->app->bind(ColumnResolver::class, ScoutColumnResolver::class);
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

    protected function registerListeners(): void {
        $this->booting(static function (Dispatcher $dispatcher): void {
            $dispatcher->subscribe(IndexExpiredListener::class);
        });
    }

    protected function registerElasticClient(): void {
        $this->app->singleton(Client::class, static function (Container $container) {
            $config = $container->make(Repository::class)->get('elastic.client');
            $logger = $config['logger'] ?? null;

            if (is_string($logger)) {
                $logger = $container->make(LogManager::class)->channel($logger);
            } elseif ($logger === true) {
                $logger = $container->make(LoggerInterface::class);
            } else {
                $logger = null;
            }

            if ($logger instanceof LoggerInterface) {
                $config['logger'] = $logger;
            } else {
                unset($config['logger']);
            }

            return ClientBuilder::fromConfig($config);
        });
    }
    // </editor-fold>

    // <editor-fold desc="Boot">
    // =========================================================================
    public function boot(): void {
        $this->bootCommands(
            IndexesRebuild::class,
        );
        $this->bootSchedule(
            AssetsIndexer::class,
            CustomersIndexer::class,
            DocumentsIndexer::class,
        );
    }
    // </editor-fold>
}
