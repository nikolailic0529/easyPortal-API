<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;

class GraphQLError extends GraphQLResponse {
    /**
     * @var array<string>|\Closure():array<string>|null
     */
    protected Closure|array|null $errors = null;

    /**
     * @param array<string>|\Closure():array<string>|null $errors
     */
    public function __construct(string $root, Closure|array|null $errors = null) {
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
