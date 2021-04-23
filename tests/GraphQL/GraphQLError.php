<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;
use Throwable;

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
     * @inheritdoc
     */
    protected function getResponseConstraints(): array {
        return [
            $this->errors
                ? new JsonMatchesSchema(new JsonSchema(new GraphQLErrorsSchema($this->errors)))
                : null,
        ];
    }
}
