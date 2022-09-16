<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Services\DataLoader\Loader\Concerns\DocumentsOperations;
use App\Services\DataLoader\Loader\Concerns\WithAssets;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @template TState of \App\Services\DataLoader\Loader\CompanyLoaderState
 *
 * @extends Loader<TState>
 */
abstract class CompanyLoader extends Loader {
    /**
     * @phpstan-use WithAssets<TState>
     */
    use WithAssets;

    /**
     * @phpstan-use DocumentsOperations<TState>
     */
    use DocumentsOperations;

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new CompanyLoaderState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'withAssets'    => $this->isWithAssets(),
            'withDocuments' => $this->isWithDocuments(),
        ]);
    }
    // </editor-fold>
}
