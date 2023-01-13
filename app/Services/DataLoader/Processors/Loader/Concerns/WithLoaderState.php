<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Concerns;

use App\Services\DataLoader\Processors\Concerns\WithForce;
use App\Services\DataLoader\Processors\Concerns\WithFrom;
use App\Services\DataLoader\Processors\Concerns\WithObjectId;
use App\Services\DataLoader\Processors\Loader\LoaderState;
use App\Utils\Processor\State;
use Illuminate\Support\Facades\Date;

use function array_merge;

trait WithLoaderState {
    use WithObjectId;
    use WithForce;
    use WithFrom;

    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new LoaderState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'objectId' => $this->getObjectId(),
            'started'  => Date::now(),
            'force'    => $this->isForce(),
            'from'     => $this->getFrom(),
        ]);
    }
}
