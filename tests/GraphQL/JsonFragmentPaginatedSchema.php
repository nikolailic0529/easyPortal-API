<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

use function mb_substr;
use function strrpos;

class JsonFragmentPaginatedSchema extends JsonFragmentSchema {
    use WithTestData;

    protected string $root;

    /**
     * @param class-string $schema
     */
    public function __construct(
        string $path,
        string $schema,
    ) {
        $position = strrpos($path, '.');

        if ($position !== false) {
            $this->root = mb_substr($path, $position);
            $path       = mb_substr($path, 0, $position - 1);
        } else {
            $this->root = $path;
            $path       = '';
        }

        parent::__construct($path, $schema);
    }

    public function getJsonSchema(): JsonSchema {
        return new SchemaWrapper(self::class, $this->root, $this->getSchema());
    }
}
