<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use function __;

class GraphQLValidationError extends GraphQLError {
    /**
     * @param array<string,string>|Closure():array<string,string>|null $errors
     */
    public function __construct(string $root) {
        parent::__construct($root, static function (): array {
            return [__('errors.validation_failed')];
        });
    }

    /**
     * @return class-string<\Tests\GraphQL\GraphQLResponse>
     */
    protected function getResponseClass(): string {
        return GraphQLError::class;
    }
}
