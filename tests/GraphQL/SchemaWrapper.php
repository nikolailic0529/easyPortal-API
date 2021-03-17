<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchemaWrapper;
use Opis\JsonSchema\ISchemaLoader;

class SchemaWrapper extends JsonSchemaWrapper {
    protected string $response;
    protected string $root;

    public function __construct(string $response, string $root, string $schema = null, ISchemaLoader $loader = null) {
        $this->response = $response;
        $this->root     = $root;
        $schema       ??= AnySchema::class;

        parent::__construct($schema, $loader);
    }

    protected function getBaseSchema(): string {
        return $this->getTestData($this->response)->content('.json');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSchemaReplacements(): array {
        return parent::getSchemaReplacements() + [
                'graphql.root' => $this->root,
            ];
    }
}
