<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\ASTCache;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

class ASTBuilderPersistent extends ASTBuilder {
    private static DocumentAST $ast;

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
            $setting = 'lighthouse.cache.enable';
            $enabled = $this->config->get($setting);

            $this->config->set($setting, false);

            try {
                return parent::documentAST();
            } finally {
                $this->config->set($setting, $enabled);
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
