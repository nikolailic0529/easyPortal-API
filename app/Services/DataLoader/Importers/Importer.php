<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\LastIdBasedIterator;
use App\Services\DataLoader\Client\OffsetBasedIterator;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Resolver;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Importer {
    use GlobalScopes;

    protected Container $container;
    protected Loader    $loader;
    protected Resolver  $resolver;
    protected ?Closure  $onChange = null;
    protected ?Closure  $onFinish = null;

    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
        private Container $appContainer,
    ) {
        // empty
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    public function onChange(?Closure $closure): static {
        $this->onChange = $closure;

        return $this;
    }

    public function onFinish(?Closure $closure): static {
        $this->onFinish = $closure;

        return $this;
    }

    public function import(
        bool $update = false,
        DateTimeInterface $from = null,
        string|int $continue = null,
        int $chunk = null,
        int $limit = null,
    ): void {
        $status   = new Status($from, $continue);
        $iterator = $this
            ->getIterator($from, $chunk, $limit, $continue)
            ->beforeChunk(function (array $items) use ($status): void {
                $this->onBeforeChunk($items, $status);
            })
            ->afterChunk(function (array $items) use (&$iterator, $status): void {
                $this->onAfterChunk($items, $status, $this->getContinue($iterator));
            });

        $this->callWithoutGlobalScopes(
            [OwnedByOrganizationScope::class],
            function () use ($iterator, $status, $update): void {
                foreach ($iterator as $item) {
                    /** @var \App\Services\DataLoader\Schema\Type|\App\Services\DataLoader\Schema\TypeWithId $item */
                    try {
                        if ($this->resolver->get($item->id)) {
                            if ($update) {
                                $this->loader->update($item);
                                $status->updated++;
                            }
                        } else {
                            $this->loader->create($item);
                            $status->created++;
                        }
                    } catch (Throwable $exception) {
                        $status->failed++;

                        $this->logger->warning('Failed to import asset.', [
                            'asset'     => $item,
                            'exception' => $exception,
                        ]);
                    } finally {
                        $status->processed++;
                    }
                }
            },
        );

        $this->onAfterImport($status);
    }

    protected function getContinue(QueryIterator $iterator): string|int|null {
        $continue = null;

        if ($iterator instanceof OffsetBasedIterator) {
            $continue = $iterator->getOffset();
        } elseif ($iterator instanceof LastIdBasedIterator) {
            $continue = $iterator->getLastId();
        } else {
            // empty
        }

        return $continue;
    }

    protected function getIterator(
        ?DateTimeInterface $from,
        ?int $chunk,
        ?int $limit,
        int|string|null $continue,
    ): QueryIterator {
        $iterator = $this->makeIterator($from);

        if ($chunk) {
            $iterator->chunk($chunk);
        }

        if ($limit) {
            $iterator->limit($limit);
        }

        if ($continue) {
            if ($iterator instanceof OffsetBasedIterator) {
                $iterator->offset($continue);
            } elseif ($iterator instanceof LastIdBasedIterator) {
                $iterator->lastId($continue);
            } else {
                throw new InvalidArgumentException('Iterator cannot be continued.');
            }
        }

        return $iterator;
    }

    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        $this->container = $this->appContainer->make(DataLoaderService::class)->getContainer();
        $this->resolver  = $this->makeResolver();
        $this->loader    = $this->makeLoader();
    }

    /**
     * @param array<mixed> $items
     */
    protected function onAfterChunk(array $items, Status $status, string|int|null $continue): void {
        // Update status
        $status->continue = $continue;
        $status->chunk++;

        // Call callback
        if ($this->onChange) {
            ($this->onChange)($items, clone $status);
        }
    }

    protected function onAfterImport(Status $status): void {
        if ($this->onFinish) {
            ($this->onFinish)(clone $status);
        }
    }

    protected function makeService(): DataLoaderService {
        return $this->appContainer->make(DataLoaderService::class);
    }

    abstract protected function makeIterator(
        DateTimeInterface $from = null,
    ): QueryIterator;

    abstract protected function makeLoader(): Loader;

    abstract protected function makeResolver(): Resolver;
}
