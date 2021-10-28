<?php declare(strict_types = 1);

namespace Tests;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\Source\SchemaStitcher;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

use function file_put_contents;
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
        if (is_null($schema)) {
            $this->app->bind(SchemaSourceProvider::class, function (): SchemaSourceProvider {
                return new SchemaStitcher(
                    $this->app->make(Repository::class)->get('lighthouse.schema.register'),
                );
            });
        } else {
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

    protected function getGraphQLSchemaExpected(string $schema = '.graphql', string $source = null): string {
        $data = $this->getTestData();

        if ($data->content($schema) === '') {
            if ($source) {
                $source = $data->content($source);
            }

            $this->assertNotFalse(file_put_contents($data->path($schema), $this->serializeGraphQLSchema($source)));
        }

        return $data->content($schema);
    }
}
