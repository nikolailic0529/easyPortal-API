<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\ASTCache;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;

class ASTBuilderPersistent extends ASTBuilder {
    protected static DocumentAST $ast;

    public function __construct(
        DirectiveLocator $directiveLocator,
        SchemaSourceProvider $schemaSourceProvider,
        EventsDispatcher $eventsDispatcher,
        ASTCache $astCache,
        protected Repository $config,
    ) {
        parent::__construct($directiveLocator, $schemaSourceProvider, $eventsDispatcher, $astCache);
    }

    public function documentAST(): DocumentAST {
        // Cached?
        static::$ast ??= parent::documentAST();

        // Return
        return static::$ast;
    }
}
