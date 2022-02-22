<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\ModelHelper;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\EloquentIterator;
use Illuminate\Database\Eloquent\Builder;

use function array_filter;
use function array_merge;
use function array_unique;

/**
 * The Processor for Eloquent Models.
 *
 * @template TItem of \Illuminate\Database\Eloquent\Model
 * @template TChunkData
 * @template TState of \App\Utils\Processor\EloquentState
 *
 * @extends \App\Utils\Processor\Processor<TItem, TChunkData, TState>
 */
abstract class EloquentProcessor extends Processor {
    /**
     * @var array<string>|null
     */
    private ?array $keys        = null;
    private bool   $withTrashed = false;

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
     *
     * @return $this<TItem, TChunkData, TState>
     */
    public function setKeys(?array $keys): static {
        $this->keys = $keys !== null
            ? array_unique(array_filter($keys))
            : null;

        return $this;
    }

    public function isWithTrashed(): bool {
        return $this->withTrashed;
    }

    public function setWithTrashed(bool $withTrashed): static {
        $this->withTrashed = $withTrashed;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Processor">
    // =========================================================================
    protected function getTotal(State $state): ?int {
        return GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($state): int {
            return $this->getBuilder($state)->count();
        });
    }

    protected function getIterator(State $state): ObjectIterator {
        return new EloquentIterator(
            $this->getBuilder($state)->getChangeSafeIterator(),
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<TItem>
     */
    protected function getBuilder(State $state): Builder {
        $class  = $this->getModel();
        $query  = $class::query();
        $model  = $query->getModel();
        $helper = new ModelHelper($model);

        if ($state->keys !== null) {
            $query = $query->whereIn($model->getKeyName(), $state->keys);
        }

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
            'keys'        => $this->getKeys(),
            'withTrashed' => $this->isWithTrashed(),
        ]);
    }
    // </editor-fold>
}
