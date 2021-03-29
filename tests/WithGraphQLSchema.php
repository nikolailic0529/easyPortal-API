<?php declare(strict_types = 1);

namespace Tests;

use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;

/**
 * @mixin \Tests\TestCase
 */
trait WithGraphQLSchema {
    use MocksResolvers;

    protected function graphQLSchema(string $schema): static {
        $this->app->bind(SchemaSourceProvider::class, static function () use ($schema): SchemaSourceProvider {
            return new TestSchemaProvider($schema);
        });

        return $this;
    }
}
