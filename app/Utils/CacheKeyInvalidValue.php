<?php declare(strict_types = 1);

namespace App\Utils;

use App\Exceptions\ApplicationException;
use Throwable;

class CacheKeyInvalidValue extends ApplicationException {
    public function __construct(mixed $value, Throwable $previous = null) {
        parent::__construct('The `$value` cannot be used as a key.', $previous);

        $this->setContext([
            'value' => $value,
        ]);
    }
}
