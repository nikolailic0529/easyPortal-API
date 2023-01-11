<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Processors\Concerns\WithForce;
use App\Services\DataLoader\Processors\Concerns\WithFrom;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeProcessor;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\MixedProcessor;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

use function array_merge;

/**
 * @template TModel of (
 *      \App\Models\Distributor|
 *      \App\Models\Reseller|
 *      \App\Models\Customer|
 *      \App\Models\Asset|
 *      \App\Models\Document
 *      )
 *
 * @extends CompositeProcessor<SynchronizerState>
 */
abstract class Synchronizer extends CompositeProcessor implements Isolated {
    use WithFrom;
    use WithForce;

    private bool               $withOutdated   = true;
    private ?DateTimeInterface $outdatedExpire = null;
    public ?int                $outdatedLimit  = null;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        protected Container $container,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContainer(): Container {
        return $this->container;
    }

    public function isWithOutdated(): bool {
        return $this->withOutdated;
    }

    public function setWithOutdated(bool $withOutdated): static {
        $this->withOutdated = $withOutdated;

        return $this;
    }

    public function getOutdatedExpire(): ?DateTimeInterface {
        return $this->outdatedExpire;
    }

    public function setOutdatedExpire(?DateTimeInterface $outdatedExpire): static {
        $this->outdatedExpire = $outdatedExpire;

        return $this;
    }

    public function getOutdatedLimit(): ?int {
        return $this->outdatedLimit;
    }

    public function setOutdatedLimit(?int $outdatedLimit): static {
        $this->outdatedLimit = $outdatedLimit;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="CompositeProcessor">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return [
            new CompositeOperation(
                'Sync objects',
                function (SynchronizerState $state): Processor {
                    return $this->getProcessor($state);
                },
            ),
            new CompositeOperation(
                'Sync outdated objects',
                function (SynchronizerState $state): ?Processor {
                    if (!$state->withOutdated) {
                        return null;
                    }

                    $iterator  = $this->getModel()::query()
                        ->where('synced_at', '<', $state->started)
                        ->when($state->outdatedExpire, static function (Builder $builder) use ($state): void {
                            $builder->where('synced_at', '<', $state->outdatedExpire);
                        })
                        ->orderBy('synced_at')
                        ->getChangeSafeIterator();
                    $iterator  = (new EloquentIterator($iterator))->setLimit($state->outdatedLimit);
                    $processor = $this->getOutdatedProcessor($state, $iterator);

                    return $processor;
                },
            ),
        ];
    }

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModel(): string;

    abstract protected function getProcessor(SynchronizerState $state): MixedProcessor;

    abstract protected function getOutdatedProcessor(SynchronizerState $state, MixedIterator $iterator): MixedProcessor;
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new SynchronizerState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'started'        => Date::now(),
            'from'           => $this->getFrom(),
            'force'          => $this->isForce(),
            'withOutdated'   => $this->isWithOutdated(),
            'outdatedLimit'  => $this->getOutdatedLimit(),
            'outdatedExpire' => $this->getOutdatedExpire(),
        ]);
    }
    // </editor-fold>
}
