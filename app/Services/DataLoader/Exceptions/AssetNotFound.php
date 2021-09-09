<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Services\DataLoader\Schema\Type;
use Throwable;

use function sprintf;

class AssetNotFound extends ObjectNotFound {
    public function __construct(string $key, Type|null $object = null, Throwable $previous = null) {
        parent::__construct(sprintf('Asset `%s` not found.', $key), $key, $object, $previous);
    }
}
