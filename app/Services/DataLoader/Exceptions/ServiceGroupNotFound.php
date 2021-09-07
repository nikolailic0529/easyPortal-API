<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Oem;
use App\Services\DataLoader\Schema\Type;
use Throwable;

use function sprintf;

class ServiceGroupNotFound extends ObjectNotFoundException {
    public function __construct(
        protected Oem $oem,
        string $key,
        Type|null $object = null,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Service Group `%s` not found (`%s`).', $key, $oem->key), $key, $object, $previous);
    }

    public function getOem(): Oem {
        return $this->oem;
    }
}
