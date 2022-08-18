<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use function trans;

class GraphQLUnauthorized extends GraphQLError {
    public function __construct(string $root) {
        parent::__construct($root, static function (): array {
            return [trans('errors.unauthorized')];
        });
    }

    protected function getResponseClass(): string {
        return GraphQLError::class;
    }
}
