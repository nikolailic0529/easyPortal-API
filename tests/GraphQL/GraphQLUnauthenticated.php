<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use function __;

class GraphQLUnauthenticated extends GraphQLError {
    public function __construct(string $root) {
        parent::__construct($root, static function (): array {
            return [__('errors.unauthenticated')];
        });
    }
}