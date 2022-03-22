<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use function __;

class GraphQLValidationError extends GraphQLError {
    public function __construct(string $root) {
        parent::__construct($root, static function (): array {
            return [__('errors.validation_failed')];
        });
    }

    /**
     * @return class-string<GraphQLResponse>
     */
    protected function getResponseClass(): string {
        return GraphQLError::class;
    }
}
