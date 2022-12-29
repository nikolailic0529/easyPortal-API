<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ParentValue {
    /**
     * @param array<mixed> $args
     */
    public function __construct(
        protected mixed $root,
        protected array $args,
        protected GraphQLContext $context,
        protected ResolveInfo $resolveInfo,
    ) {
        // empty
    }

    public function getRoot(): mixed {
        return $this->root;
    }

    /**
     * @return array<mixed>
     */
    public function getArgs(): array {
        return $this->args;
    }

    public function getContext(): GraphQLContext {
        return $this->context;
    }

    public function getResolveInfo(): ResolveInfo {
        return $this->resolveInfo;
    }
}
