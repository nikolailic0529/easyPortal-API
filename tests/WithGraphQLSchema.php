<?php declare(strict_types = 1);

namespace Tests;

use GraphQL\Utils\SchemaPrinter;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

/**
 * @mixin \Tests\TestCase
 */
trait WithGraphQLSchema {
    use MocksResolvers;

    protected function useGraphQLSchema(string $schema): static {
        $this->app->bind(SchemaSourceProvider::class, static function () use ($schema): SchemaSourceProvider {
            return new TestSchemaProvider($schema);
        });

        return $this;
    }

    protected function getGraphQLSchema(string $schema): string {
        $this->useGraphQLSchema($schema);

        $graphql = $this->app->make(GraphQL::class);
        $schema  = $graphql->prepSchema();
        $schema  = SchemaPrinter::doPrint($schema);

        return $schema;
    }
}
