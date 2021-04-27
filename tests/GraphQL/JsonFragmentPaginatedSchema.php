<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

class JsonFragmentPaginatedSchema extends JsonFragmentSchema {
    use WithTestData;

    /**
     * @param class-string $schema
     */
    public function __construct(
        protected string $root,
        string $path,
        string $schema,
    ) {
        parent::__construct($path, $schema);
    }

    public function getRoot(): string {
        return $this->root;
    }

    public function getJsonSchema(): JsonSchema {
        return new SchemaWrapper(GraphQLPaginated::class, $this->getRoot(), $this->getSchema());
    }
}
