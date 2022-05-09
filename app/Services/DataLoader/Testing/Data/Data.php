<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Models\Document as DocumentModel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Normalizer\Normalizer;
use Closure;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;
use Symfony\Component\Filesystem\Filesystem;

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

    public const MAP = 'map.json';

    public function __construct(
        protected Kernel $kernel,
        protected Application $app,
        protected Repository $config,
        protected Generator $faker,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    /**
     * @return bool|array<string,mixed>
     */
    public function generate(string $path): array|bool {
        $result   = false;
        $bindings = $this->generateBindings();

        try {
            foreach ($bindings as $abstract => $concrete) {
                if (!$this->app->bound($abstract)) {
                    $this->app->bind($abstract, $concrete);
                } else {
                    unset($bindings[$abstract]);
                }
            }

            $result = $this->generateData($path);
        } finally {
            foreach ($bindings as $abstract => $concrete) {
                unset($this->app[$abstract]);
            }
        }

        if ($result) {
            $result = $this->generateContext($path);
        }

        return $result;
    }

    /**
     * @return array<class-string,class-string>
     */
    protected function generateBindings(): array {
        return [];
    }

    /**
     * @return array<mixed>
     */
    protected function generateContext(string $path): array {
        return [];
    }

    abstract protected function generateData(string $path): bool;

    /**
     * @param array<string,mixed> $context
     */
    public function restore(string $path, array $context): bool {
        $result   = true;
        $settings = [];

        if ($context[ClientDumpContext::OEMS] ?? null) {
            $result = $result
                && $this->kernel->call('ep:data-loader-oems-import', [
                    'file' => "{$path}/{$context[ClientDumpContext::OEMS]}",
                ]) === Command::SUCCESS;
        }

        if ($context[ClientDumpContext::DISTRIBUTORS] ?? null) {
            foreach ((array) $context[ClientDumpContext::DISTRIBUTORS] as $distributor) {
                $result = $result
                    && $this->kernel->call('ep:data-loader-distributor-update', [
                        'id' => $distributor,
                    ]) === Command::SUCCESS;
            }
        }

        if ($context[ClientDumpContext::RESELLERS] ?? null) {
            foreach ((array) $context[ClientDumpContext::RESELLERS] as $reseller) {
                $result = $result
                    && $this->kernel->call('ep:data-loader-reseller-update', [
                        'id' => $reseller,
                    ]) === Command::SUCCESS;
            }
        }

        if ($context[ClientDumpContext::CUSTOMERS] ?? null) {
            foreach ((array) $context[ClientDumpContext::CUSTOMERS] as $customer) {
                $result = $result
                    && $this->kernel->call('ep:data-loader-customer-update', [
                        'id' => $customer,
                    ]) === Command::SUCCESS;
            }
        }

        if ($context[ClientDumpContext::ASSETS] ?? null) {
            foreach ((array) $context[ClientDumpContext::ASSETS] as $asset) {
                $result = $result
                    && $this->kernel->call('ep:data-loader-asset-update', [
                        'id'             => $asset,
                        '--no-documents' => true,
                    ]) === Command::SUCCESS;
            }
        }

        if ($context[ClientDumpContext::TYPES] ?? null) {
            $owner = (new DocumentModel())->getMorphClass();

            foreach ((array) $context[ClientDumpContext::TYPES] as $key) {
                // Create
                $type              = new TypeModel();
                $type->object_type = $owner;
                $type->key         = $this->normalizer->string($key);
                $type->name        = $this->normalizer->string($key);

                $type->save();

                // Collect settings
                if (mb_stripos($key, 'contract') !== false) {
                    $settings['ep.contract_types'][] = $type->getKey();
                } elseif (mb_stripos($key, 'quote') !== false) {
                    $settings['ep.quote_types'][] = $type->getKey();
                } else {
                    // empty
                }
            }
        }

        // Update settings
        foreach ($settings as $setting => $value) {
            $this->config->set($setting, $value);
        }

        // Return
        return $result;
    }

    protected function dumpClientResponses(string $path, Closure $closure): bool {
        $previous = $this->config->get('ep.data_loader.dump');

        $this->config->set('ep.data_loader.dump', $path);

        try {
            return $closure($path) && $this->cleanClientDumps($path);
        } finally {
            $this->config->set('ep.data_loader.dump', $previous);
        }
    }

    protected function cleanClientDumps(string $path): bool {
        $map     = static::MAP;
        $data    = $this->getTestData();
        $cleaner = $this->app->make(ClientDataCleaner::class);

        if ($data->file($map)->isFile()) {
            $cleaner = $cleaner->setDefaultMap($data->json($map));
        }

        foreach ((new ClientDumpsIterator($path))->getResponseIterator(true) as $object) {
            $cleaner->clean($object);
        }

        return $this->saveMap($data->path($map), $cleaner->getMap());
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
