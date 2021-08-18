<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Schema\Type;

use function sprintf;

class ServiceLevelNotFound extends InvalidData {
    public function __construct(
        protected Oem $oem,
        protected ServiceGroup $group,
        protected string $sku,
        protected Type|null $object = null,
    ) {
        parent::__construct(sprintf(
            'Service Level `%s`/`%s` not found (`%s`).',
            $this->group->sku,
            $sku,
            $this->oem->key,
        ));
    }

    public function getOem(): Oem {
        return $this->oem;
    }

    public function getGroup(): ServiceGroup {
        return $this->group;
    }

    public function getSku(): string {
        return $this->sku;
    }

    public function getObject(): ?Type {
        return $this->object;
    }
}
