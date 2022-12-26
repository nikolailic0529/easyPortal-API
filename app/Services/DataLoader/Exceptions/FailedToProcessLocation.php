<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Schema\Types\Location;
use Throwable;

use function sprintf;

class FailedToProcessLocation extends FailedToProcessObject {
    public function __construct(
        protected Reseller|Customer $model,
        protected Location $location,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to process Location for %s `%s`.',
            $this->model->getMorphClass(),
            $this->model->getKey(),
        ), $previous);

        $this->setContext([
            'location' => $this->location,
        ]);
    }
}
