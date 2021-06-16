<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Type;

use function sprintf;

class DistributorNotFoundException extends InvalidData {
    public function __construct(
        protected string $id,
        protected Type|null $object = null,
    ) {
        parent::__construct(sprintf(
            'Distributor `%s` not found.',
            $id,
        ));
    }

    public function getId(): string {
        return $this->id;
    }

    public function getObject(): ?Type {
        return $this->object;
    }
}
