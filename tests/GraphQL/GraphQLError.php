<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchemaValue;
use Throwable;

use function array_merge;

class GraphQLError extends GraphQLResponse {
    /**
     * @var array<string>|\Throwable|\Closure():array<string>|null
     */
    protected Throwable|Closure|array|null $errors = null;

    /**
     * @param array<string>|\Throwable|\Closure():array<string>|null $errors
     */
    public function __construct(string $root, Throwable|Closure|array|null $errors = null) {
        $this->errors = $errors;

        parent::__construct($root, null);
    }

    /**
     * @inheritDoc
     */
    protected function getSchemaConstraints(): array {
        return array_merge(parent::getSchemaConstraints(), [
            new JsonMatchesSchema(new SchemaWrapper(self::class, $this->root)),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getResponseConstraints(): array {
        return [
            $this->errors
                ? new JsonMatchesSchema(new JsonSchemaValue(new GraphQLErrorsSchema($this->errors)))
                : null,
        ];
    }
}
