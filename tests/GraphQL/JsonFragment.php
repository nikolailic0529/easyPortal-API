<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use SplFileInfo;
use stdClass;

class JsonFragment {
    public function __construct(
        protected string $path,
        protected JsonSerializable|SplFileInfo|stdClass|array|string|int|float|bool|null $json,
    ) {
        // empty
    }

    public function getPath(): string {
        return $this->path;
    }

    public function setPath(string $path): static {
        $this->path = $path;

        return $this;
    }

    public function getJson(): JsonSerializable|SplFileInfo|stdClass|array|string|int|float|bool|null {
        return $this->json;
    }
}
