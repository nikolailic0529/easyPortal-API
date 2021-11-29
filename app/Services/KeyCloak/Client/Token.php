<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Services\KeyCloak\KeyCloak;
use App\Services\KeyCloak\OAuth2\Provider;
use App\Services\Tokens\OAuth2Token;
use App\Services\Tokens\Service;

class Token extends OAuth2Token {
    public function __construct(
        Service $service,
        protected KeyCloak $keyCloak,
    ) {
        parent::__construct($service);
    }

    protected function getProvider(): Provider {
        return $this->keyCloak->getProvider();
    }
}
