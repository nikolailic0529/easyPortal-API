<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use Illuminate\Contracts\Config\Repository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;

class Jwt {
    protected Configuration $configuration;

    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    public function decode(string $token): Token {
        // FIXME [!] Validation

        return $this->getConfiguration()->parser()->parse($token);
    }

    protected function getConfiguration(): Configuration {
        if (!isset($this->configuration)) {
            // FIXME [!] Public key
            $this->configuration = Configuration::forUnsecuredSigner();
        }

        return $this->configuration;
    }
}
