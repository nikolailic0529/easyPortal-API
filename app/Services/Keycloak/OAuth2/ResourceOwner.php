<?php declare(strict_types = 1);

namespace App\Services\Keycloak\OAuth2;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ResourceOwner implements ResourceOwnerInterface {
    /**
     * @param array<string,mixed> $owner
     */
    public function __construct(
        protected array $owner,
    ) {
        // empty
    }

    public function getId(): ?string {
        return $this->owner['sub'] ?? null;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array {
        return $this->owner;
    }
}
