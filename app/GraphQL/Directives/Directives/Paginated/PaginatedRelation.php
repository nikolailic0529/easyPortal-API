<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Directives\Relation;
use App\Utils\Eloquent\Callbacks\OrderByKey;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use LogicException;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;

use function sprintf;

abstract class PaginatedRelation extends Base {
    public function __construct(
        Container $container,
        OrderByKey $orderByCallback,
        protected Repository $config,
        protected DirectiveLocator $directives,
    ) {
        parent::__construct($container, $orderByCallback);
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Adds offset-based pagination for the relation.
            """
            directive @paginatedRelation on FIELD_DEFINITION
            GRAPHQL;
    }

    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        // Relation?
        $directive = Relation::class;
        $exists    = $this->directives
            ->associatedOfType($fieldDefinition, $directive)
            ->isNotEmpty();

        if (!$exists) {
            throw new LogicException(sprintf(
                '@paginatedRelation directive should be used with one of `%s`.',
                '@'.DirectiveLocator::directiveName($directive),
            ));
        }

        // Transform
        parent::manipulateFieldDefinition($documentAST, $fieldDefinition, $parentType);
    }

    /**
     * @return array<string>
     */
    public function getBuilderArguments(): array {
        $directive = $this->directives->associatedOfType($this->definitionNode, RelationDirective::class)->first();
        $relation  = (new class() extends RelationDirective {
            /**
             * @noinspection PhpMissingParentConstructorInspection
             * @phpstan-ignore-next-line
             */
            public function __construct() {
                // no need to call parent
            }

            public static function definition(): string {
                return '';
            }

            public function getRelation(RelationDirective $directive): string {
                return $directive->relation();
            }
        })->getRelation($directive);

        return [
            'relation' => $relation,
        ];
    }
}
