<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Schema;
use GraphQL\Utils\BuildClientSchema;
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
            $expected = $this->getTestData(Schema::class)->content('.graphql');

            self::assertGraphQLSchemaEquals($expected, $actual);
        } else {
            self::markTestSkipped('DataLoader is disabled.');
        }
    }
}
