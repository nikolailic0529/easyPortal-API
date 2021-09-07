<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Type;
use Throwable;

use function sprintf;

class ResellerNotFound extends ObjectNotFound {
    public function __construct(string $key, Type|null $object = null, Throwable $previous = null) {
        parent::__construct(sprintf('Reseller `%s` not found.', $key), $key, $object, $previous);
    }
}
