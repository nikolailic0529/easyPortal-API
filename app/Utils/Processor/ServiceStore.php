<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Services\Service;
use App\Utils\Processor\Contracts\StateStore;

use function is_array;

class ServiceStore implements StateStore {
    public function __construct(
        protected Service $service,
        protected mixed $key,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public function get(): ?array {
        return $this->service->get($this->key, static function (mixed $state): ?array {
            return is_array($state) ? $state : null;
        });
    }

    public function save(State $state): State {
        return $this->service->set($this->key, $state);
    }

    public function delete(): bool {
        return $this->service->delete($this->key);
    }
}
