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
            $setting = 'lighthouse.cache.enable';
            $enabled = $this->configRepository->get($setting);

            $this->configRepository->set($setting, false);

            try {
                return parent::documentAST();
            } finally {
                $this->configRepository->set($setting, $enabled);
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
