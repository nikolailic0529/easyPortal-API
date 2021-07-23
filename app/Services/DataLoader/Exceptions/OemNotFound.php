<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Type;

use function sprintf;

class OemNotFound extends InvalidData {
    public function __construct(
        protected string $key,
        protected Type|null $object = null,
    ) {
        parent::__construct(sprintf(
            'Oem `%s` not found.',
            $key,
        ));
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getObject(): ?Type {
        return $this->object;
    }
}
