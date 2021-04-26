<?php declare(strict_types = 1);

namespace Tests\GraphQL;

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

    public function getSchema(): mixed {
        return $this->getTestData($this->schema)->json();
    }
}
