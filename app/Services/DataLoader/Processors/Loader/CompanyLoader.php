<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader;

use App\Services\DataLoader\Processors\Loader\Concerns\DocumentsOperations;
use App\Services\DataLoader\Processors\Loader\Concerns\WithAssets;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @template TState of CompanyLoaderState
 *
 * @extends Loader<TState>
 */
abstract class CompanyLoader extends Loader {
    /**
     * @use WithAssets<TState>
     */
    use WithAssets;

    /**
     * @use DocumentsOperations<TState>
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
