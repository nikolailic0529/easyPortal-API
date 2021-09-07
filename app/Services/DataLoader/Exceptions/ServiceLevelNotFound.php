<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Schema\Type;
use Throwable;

use function sprintf;

class ServiceLevelNotFound extends ObjectNotFound {
    public function __construct(
        protected Oem $oem,
        protected ServiceGroup $serviceGroup,
        string $key,
        Type|null $object = null,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Service Level `%s`/`%s` not found (`%s`).',
            $serviceGroup->sku,
            $key,
            $oem->key,
        ), $key, $object, $previous);
    }

    public function getOem(): Oem {
        return $this->oem;
    }

    public function getServiceGroup(): ServiceGroup {
        return $this->serviceGroup;
    }
}
