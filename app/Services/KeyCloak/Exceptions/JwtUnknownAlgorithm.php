<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use Throwable;

use function sprintf;

class JwtUnknownAlgorithm extends JwtException {
    public function __construct(string $algorithm, Throwable $previous = null) {
        parent::__construct(sprintf('JWT: Algorithm `%s` is unknown.', $algorithm), 0, $previous);
    }
}
