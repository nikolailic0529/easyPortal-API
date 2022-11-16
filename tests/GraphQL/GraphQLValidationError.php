<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchemaValue;

/**
 * @phpstan-import-type ValidationErrors from GraphQLValidationErrorsSchema
 */
class GraphQLValidationError extends GraphQLResponse {
    /**
     * @template T
     *
     * @param ValidationErrors|Closure(T):ValidationErrors $errors
     */
    public function __construct(
        string $root,
        protected Closure|array $errors,
    ) {
        parent::__construct($root, null);
    }

    protected function getResponseClass(): string {
        return GraphQLError::class;
    }

    /**
     * @inheritdoc
     */
    protected function getResponseConstraints(): array {
        return [
            new JsonMatchesSchema(new JsonSchemaValue(new GraphQLValidationErrorsSchema($this->errors))),
        ];
    }
}
