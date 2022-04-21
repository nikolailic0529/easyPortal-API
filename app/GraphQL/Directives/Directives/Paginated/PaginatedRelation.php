<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use LogicException;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\BelongsToManyDirective;
use Nuwave\Lighthouse\Schema\Directives\HasManyDirective;
use Nuwave\Lighthouse\Schema\Directives\MorphManyDirective;
use Nuwave\Lighthouse\Schema\Directives\RelationDirective;

use function sprintf;

abstract class PaginatedRelation extends Base {
    public function __construct(
        Container $container,
        protected Repository $config,
        protected DirectiveLocator $directives,
    ) {
        parent::__construct($container);
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
        $exists    = false;
        $relations = [
            HasManyDirective::class,
            MorphManyDirective::class,
            BelongsToManyDirective::class,
        ];

        foreach ($relations as $relation) {
            if (!$this->directives->associatedOfType($fieldDefinition, $relation)->isEmpty()) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            throw new LogicException(sprintf(
                '@paginatedRelation directive should be used with one of `%s`.',
                (new Collection($relations))
                    ->map(static function (string $class): string {
                        return '@'.DirectiveLocator::directiveName($class);
                    })
                    ->implode('`, `'),
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
