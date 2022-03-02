<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use Throwable;

class JwtDecodingFailed extends JwtException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('JWT: Decoding failed.', $previous);
    }
}
