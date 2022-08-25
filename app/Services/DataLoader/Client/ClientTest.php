<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Schema;
use GraphQL\Utils\BuildClientSchema;
use LastDragon_ru\LaraASP\GraphQL\Testing\GraphQLExpectedSchema;
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

        if ($client->isEnabled()) {
            $into     = $client->getIntrospection();
            $actual   = BuildClientSchema::build($into);
            $expected = (new GraphQLExpectedSchema(
                $this->getTestData(Schema::class)->content('.graphql'),
            ))
                ->setSettings(
                    (new TestSettings())->setPrintUnusedDefinitions(true),
                );

            self::assertGraphQLSchemaEquals($expected, $actual);
        } else {
            self::markTestSkipped('DataLoader is disabled.');
        }
    }
}
