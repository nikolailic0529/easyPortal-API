<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

class JsonFragmentSchema {
    use WithTestData;

    /**
     * @param class-string $schema
     */
    public function __construct(
        protected string $path,
        protected string $schema,
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

    /**
     * @return class-string
     */
    public function getSchema(): string {
        return $this->schema;
    }

    public function getJsonSchema(): JsonSchema {
        return new JsonSchema($this->getTestData($this->getSchema())->json());
    }
}
