<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use function trans;

class GraphQLValidationError extends GraphQLError {
    public function __construct(string $root) {
        parent::__construct($root, static function (): array {
            return [trans('errors.validation_failed')];
        });
    }

    /**
     * @return class-string<GraphQLResponse>
     */
    protected function getResponseClass(): string {
        return GraphQLError::class;
    }
}
