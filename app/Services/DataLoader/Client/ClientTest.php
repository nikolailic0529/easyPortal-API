<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Schema;
use GraphQL\Utils\BuildClientSchema;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client\Client
 */
class ClientTest extends TestCase {
    use WithGraphQLSchema;

    /**
     * @group integration
     *
     * @coversNothing
     */
    public function testSchema(): void {
        $client = $this->app->make(Client::class);

        if (!$client->isEnabled()) {
            self::markTestSkipped('DataLoader is disabled.');
        }

        $into     = $client->getIntrospection();
        $actual   = BuildClientSchema::build($into);
        $actual   = (string) $this
            ->getGraphQLSchemaPrinter(
                (new TestSettings())->setPrintUnusedDefinitions(true),
            )
            ->printSchema($actual);
        $expected = $this->getTestData(Schema::class)->file('.graphql');

        self::assertGraphQLSchemaCompatible(
            $expected,
            $actual,
        );
    }
    // </editor-fold>
}
