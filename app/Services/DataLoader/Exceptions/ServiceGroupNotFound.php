<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Oem;
use App\Services\DataLoader\Schema\Type;

use function sprintf;

class ServiceGroupNotFound extends InvalidData {
    public function __construct(
        protected Oem $oem,
        protected string $sku,
        protected Type|null $object = null,
    ) {
        parent::__construct(sprintf(
            'Service Group `%s` not found (`%s`).',
            $sku,
            $this->oem->key,
        ));
    }

    public function getOem(): Oem {
        return $this->oem;
    }

    public function getSku(): string {
        return $this->sku;
    }

    public function getObject(): ?Type {
        return $this->object;
    }
}
