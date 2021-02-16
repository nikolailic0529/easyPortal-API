<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchemaWrapper;
use Opis\JsonSchema\ISchemaLoader;

use function strtr;

class GraphQLResponse extends JsonSchemaWrapper {
    protected string $root;

    public function __construct(string $root, ?string $schema, ISchemaLoader $loader = null) {
        $this->root = $root;
        $schema   ??= NullSchema::class;

        parent::__construct($schema, $loader);
    }

    protected function getSchemaFor(string $schema): string {
        $schema = parent::getSchemaFor($schema);
        $schema = strtr($schema, [
            '${schema.root}' => $this->root,
        ]);

        return $schema;
    }
}
