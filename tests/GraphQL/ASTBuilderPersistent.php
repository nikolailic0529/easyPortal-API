<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

class ASTBuilderPersistent extends ASTBuilder {
    private static DocumentAST $ast;

    public function documentAST(): DocumentAST {
        // Test?
        if ($this->schemaSourceProvider instanceof TestSchemaProvider) {
            return parent::documentAST();
        }

        // Cached?
        if (!isset(static::$ast)) {
            static::$ast = parent::documentAST();
        }

        // Return
        return static::$ast;
    }
}
