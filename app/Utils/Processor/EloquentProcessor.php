<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Eloquent\ModelHelper;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use App\Utils\Iterators\Eloquent\ModelsIterator;
use Illuminate\Database\Eloquent\Builder;

use function array_filter;
use function array_merge;
use function array_unique;
use function count;

/**
 * The Processor for Eloquent Models.
 *
 * @template TItem of \Illuminate\Database\Eloquent\Model
 * @template TChunkData
 * @template TState of \App\Utils\Processor\EloquentState<TItem>
 *
 * @extends IteratorProcessor<TItem, TChunkData, TState>
 */
abstract class EloquentProcessor extends IteratorProcessor {
    /**
     * @var array<string>|null
     */
    private ?array $keys = null;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @return array<string>|null
     */
    public function getKeys(): ?array {
        return $this->keys;
    }

    /**
     * @param array<string>|null $keys
     */
    public function setKeys(?array $keys): static {
        $this->keys = $keys !== null
            ? array_unique(array_filter($keys))
            : null;

        return $this;
    }

    public function isWithTrashed(): bool {
        return false;
    }
    // </editor-fold>

    // <editor-fold desc="Processor">
    // =========================================================================
    protected function getTotal(State $state): ?int {
        return $this->call(function () use ($state): int {
            return $state->keys === null
                ? $this->getBuilder($state)->count()
                : count($state->keys);
        });
    }

    protected function getIterator(State $state): ObjectIterator {
        $builder  = $this->getBuilder($state);
        $iterator = $state->keys === null
            ? new EloquentIterator($builder->getChangeSafeIterator())
            : new ModelsIterator($builder, $state->keys);

        return $iterator;
    }

    /**
     * @param TState $state
     *
     * @return Builder<TItem>
     */
    protected function getBuilder(State $state): Builder {
        $class  = $state->model;
        $query  = $class::query();
        $helper = new ModelHelper($query->getModel());

        if ($helper->isSoftDeletable()) {
            if ($state->withTrashed) {
                $query = $query->withTrashed();
            } else {
                $query = $query->withoutTrashed();
            }
        }

        return $query;
    }

    /**
     * @return class-string<TItem>
     */
    abstract protected function getModel(): string;
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new EloquentState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'model'       => $this->getModel(),
            'keys'        => $this->getKeys(),
            'withTrashed' => $this->isWithTrashed(),
        ]);
    }
    // </editor-fold>
}
