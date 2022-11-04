<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;

final class QueryOperation {
    /**
     * @param array<string, FragmentDefinitionNode> $fragments
     */
    public function __construct(
        protected OperationDefinitionNode $operation,
        protected array $fragments,
    ) {
        // empty
    }

    public function getOperation(): OperationDefinitionNode {
        return $this->operation;
    }

    public function getFragment(string $name): ?FragmentDefinitionNode {
        return $this->fragments[$name] ?? null;
    }
}
