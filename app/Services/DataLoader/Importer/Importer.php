<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Exceptions\FailedToImportObject;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\LoaderRecalculable;
use App\Services\DataLoader\Resolver\Resolver;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use DateTimeInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

use function array_merge;

/**
 * @template TItem
 * @template TChunkData of \App\Services\DataLoader\Collector\Data
 * @template TState of \App\Services\DataLoader\Importer\ImporterState
 *
 * @extends \App\Utils\Processor\Processor<TItem, TChunkData, TState>
 */
abstract class Importer extends Processor {
    private ?DateTimeInterface $from   = null;
    private bool               $update = true;
    private Loader             $loader;
    private Resolver           $resolver;
    private Collector          $collector;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        private Client $client,
        private Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);

        $this->register();
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getClient(): Client {
        return $this->client;
    }

    protected function getContainer(): Container {
        return $this->container;
    }

    public function getFrom(): ?DateTimeInterface {
        return $this->from;
    }

    public function setFrom(?DateTimeInterface $from): static {
        $this->from = $from;

        return $this;
    }

    public function isUpdate(): bool {
        return $this->update;
    }

    public function setUpdate(bool $update): static {
        $this->update = $update;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Import">
    // =========================================================================
    protected function report(Throwable $exception, mixed $item = null): void {
        $this->getExceptionHandler()->report(
            new FailedToImportObject($this, $item, $exception),
        );
    }

    /**
     * @param TState $state
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        if ($this->resolver->get($item->id)) {
            if ($state->update) {
                $this->loader->update($item);
                $state->updated++;
            } else {
                $state->ignored++;
            }
        } else {
            $this->loader->create($item);
            $state->created++;
        }
    }

    /**
     * @inheritDoc
     */
    protected function chunkLoaded(State $state, array $items, mixed $data): void {
        // Reset objects
        $this->collector = $this->getContainer()->make(Collector::class);
        $this->resolver  = $this->makeResolver($state);
        $this->loader    = $this->makeLoader($state);

        // Configure
        if ($this->loader instanceof LoaderRecalculable) {
            $this->loader->setRecalculate(false);
        }

        $this->collector->subscribe($data);

        // Parent
        parent::chunkLoaded($state, $items, $data);
    }

    /**
     * @inheritDoc
     */
    protected function chunkProcessed(State $state, array $items, mixed $data): void {
        // Update calculated properties
        if ($this->loader instanceof LoaderRecalculable && ($state->created || $state->updated || $state->failed)) {
            $this->loader->recalculate(true);
        }

        // Reset container
        $this->getContainer()->forgetInstances();

        // Unset
        unset($this->collector);
        unset($this->resolver);
        unset($this->loader);

        // Parent
        parent::chunkProcessed($state, $items, $data);
    }

    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return new DataImported($data);
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new ImporterState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'from'   => $this->getFrom(),
            'update' => $this->isUpdate(),
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="Abstract">
    // =========================================================================
    abstract protected function register(): void;

    /**
     * @param TState $state
     */
    abstract protected function makeLoader(State $state): Loader;

    /**
     * @param TState $state
     */
    abstract protected function makeResolver(State $state): Resolver;
    // </editor-fold>
}
