<?php declare(strict_types = 1);

namespace Tests;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

use function is_null;

/**
 * @mixin \Tests\TestCase
 */
trait WithGraphQLSchema {
    use MocksResolvers;

    /**
     * Compares two GraphQL schemas.
     */
    public function assertGraphQLSchemaEquals(
        string $expected,
        string|null $schema = null,
        string $message = '',
    ): void {
        $this->assertEquals(
            $expected,
            $this->serializeGraphQLSchema($schema),
            $message,
        );
    }

    protected function useGraphQLSchema(string|null $schema): static {
        if (!is_null($schema)) {
            $this->app->bind(SchemaSourceProvider::class, static function () use ($schema): SchemaSourceProvider {
                return new TestSchemaProvider($schema);
            });
        }

        return $this;
    }

    protected function getGraphQLSchema(string|null $schema = null): Schema {
        $this->useGraphQLSchema($schema);

        $graphql = $this->app->make(SchemaBuilder::class);
        $schema  = $graphql->schema();

        return $schema;
    }

    protected function serializeGraphQLSchema(string|null $schema = null): string {
        return SchemaPrinter::doPrint($this->getGraphQLSchema($schema));
    }
}
