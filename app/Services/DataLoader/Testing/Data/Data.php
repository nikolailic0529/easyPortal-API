<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Models\Data\Type as TypeModel;
use App\Models\Document as DocumentModel;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Testing\Data\Client as DataClient;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use Closure;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function array_combine;
use function json_encode;
use function ksort;
use function mb_stripos;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class Data {
    use WithTestData;

    public const MAP   = 'map.json';
    public const LIMIT = 25;
    public const CHUNK = 5;

    public function __construct(
        protected Kernel $kernel,
        protected Application $app,
        protected Repository $config,
        protected Generator $faker,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    public function generate(string $path): ?Context {
        // Context
        $context = new Context([
            Context::FILES => [Data::MAP],
        ]);

        // Generate
        $bindings = $this->generateBindings();

        try {
            foreach ($bindings as $abstract => $concrete) {
                if (!$this->app->bound($abstract)) {
                    $this->app->bind($abstract, $concrete);
                } else {
                    unset($bindings[$abstract]);
                }
            }

            $result = $this->dumpClientResponses($path, $context, function () use ($path, $context): bool {
                return $this->generateData($path, $context);
            });

            if (!$result) {
                return null;
            }
        } finally {
            foreach ($bindings as $abstract => $concrete) {
                unset($this->app[$abstract]);
            }
        }

        // Add Context
        $supported   = $this->getSupporterContext();
        $dumpContext = $this->app->make(ClientDumpContext::class)->get($path);

        foreach ($supported as $type) {
            $context[$type] = $dumpContext[$type] ?? [];
        }

        // Cleanup
        $fs     = new Filesystem();
        $finder = (new Finder())->in($path)->files();

        foreach ($context[Context::FILES] as $file) {
            $finder = $finder->notPath($file);
        }

        foreach ($context[Context::OEMS] as $oem) {
            $finder = $finder->notPath($oem);
        }

        $fs->remove($finder);

        // Return
        return $context;
    }

    /**
     * @return array<class-string,class-string>
     */
    protected function generateBindings(): array {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getSupporterContext(): array {
        return [];
    }

    abstract protected function generateData(string $path, Context $context): bool;

    public function restore(string $path, Context $context): bool {
        // Oems
        foreach ($context[Context::OEMS] as $oem) {
            $result = $this->kernel->call('ep:data-loader-oems-import', [
                'file' => "{$path}/{$oem}",
            ]);

            if ($result !== Command::SUCCESS) {
                return false;
            }
        }

        // Distributors
        foreach ($context[Context::DISTRIBUTORS] as $distributor) {
            $result = $this->kernel->call('ep:data-loader-distributor-sync', [
                'id' => $distributor,
            ]);

            if ($result !== Command::SUCCESS) {
                return false;
            }
        }

        // Resellers
        foreach ($context[Context::RESELLERS] as $reseller) {
            $result = $this->kernel->call('ep:data-loader-reseller-sync', [
                'id' => $reseller,
            ]);

            if ($result !== Command::SUCCESS) {
                return false;
            }
        }

        // Customers
        foreach ($context[Context::CUSTOMERS] as $customer) {
            $result = $this->kernel->call('ep:data-loader-customer-sync', [
                'id' => $customer,
            ]);

            if ($result !== Command::SUCCESS) {
                return false;
            }
        }

        // Assets
        foreach ($context[Context::ASSETS] as $asset) {
            $result = $this->kernel->call('ep:data-loader-asset-sync', [
                'id' => $asset,
            ]);

            if ($result !== Command::SUCCESS) {
                return false;
            }
        }

        // Types
        $settings = [];
        $owner    = (new DocumentModel())->getMorphClass();

        foreach ($context[Context::TYPES] as $key) {
            // Create
            $key  = $this->normalizer->string($key);
            $type = TypeModel::query()->where('object_type', '=', $owner)->where('key', '=', $key)->first();

            if (!$type) {
                $type              = new TypeModel();
                $type->object_type = $owner;
                $type->key         = $key;
                $type->name        = $key;

                $type->save();
            }

            // Collect settings
            if (mb_stripos($key, 'contract') !== false) {
                $settings['ep.contract_types'][] = $type->getKey();
            } elseif (mb_stripos($key, 'quote') !== false) {
                $settings['ep.quote_types'][] = $type->getKey();
            } else {
                // empty
            }
        }

        // Update settings
        foreach ($settings as $setting => $value) {
            $this->config->set($setting, $value);
        }

        // Return
        return true;
    }

    /**
     * @param Closure(string): bool $closure
     */
    private function dumpClientResponses(string $path, Context $context, Closure $closure): bool {
        $map     = self::MAP;
        $data    = $this->getTestData();
        $cleaner = $this->app->make(ClientDataCleaner::class);

        if ($data->file($map)->isFile()) {
            $cleaner = $cleaner->setDefaultMap($data->json($map));
        }

        $this->app->bind(Client::class, function () use ($path, $context, $cleaner): Client {
            return $this->app->make(DataClient::class)
                ->setContext($context)
                ->setCleaner($cleaner)
                ->setLimit(static::LIMIT)
                ->setData(static::class)
                ->setPath($path);
        });

        try {
            return $closure($path)
                && $this->saveMap($data->path($map), $cleaner->getMap());
        } finally {
            unset($this->app[Client::class]);
        }
    }

    /**
     * @template T of \App\Utils\Eloquent\Model
     *
     * @param class-string<T> $model
     * @param array<string>   $keys
     *
     * @return ObjectIterator<T|string>
     */
    protected static function getModelsIterator(string $model, array $keys): ObjectIterator {
        $data   = array_combine($keys, $keys);
        $models = GlobalScopes::callWithoutAll(static function () use ($model, $keys): Collection {
            $model  = new $model();
            $models = $model::query()
                ->whereIn($model->getKeyName(), $keys)
                ->get();

            return $models;
        });

        foreach ($models as $m) {
            $data[$m->getKey()] = $m;
        }

        return new ObjectsIterator(
            $data,
        );
    }

    /**
     * @param array<string, mixed> $map
     */
    private function saveMap(string $path, array $map): bool {
        ksort($map);

        (new Filesystem())->dumpFile(
            $path,
            json_encode(
                $map,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_LINE_TERMINATORS
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR,
            ),
        );

        return true;
    }
}
