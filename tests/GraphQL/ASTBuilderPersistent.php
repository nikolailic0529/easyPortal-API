<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\ASTCache;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

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
        // Test?
        if ($this->schemaSourceProvider instanceof TestSchemaProvider) {
            $cache          = $this->astCache;
            $this->astCache = new class($this->config) extends ASTCache {
                public function isEnabled(): bool {
                    return false;
                }

                public function fromCacheOrBuild(Closure $build): DocumentAST {
                    return $build();
                }
            };

            try {
                return parent::documentAST();
            } finally {
                $this->astCache = $cache;
            }
        }

        // Cached?
        if (!isset(static::$ast)) {
            static::$ast = parent::documentAST();
        }

        // Return
        return static::$ast;
    }
}
