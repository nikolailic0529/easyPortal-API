<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Schema;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\SchemaPrinter;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client\Client
 */
class ClientTest extends TestCase {
    /**
     * @group integration
     *
     * @coversNothing
     */
    public function testSchema(): void {
        $client = $this->app->make(Client::class);

        if ($client->isEnabled()) {
            $into     = $client->getIntrospection();
            $actual   = SchemaPrinter::doPrint(BuildClientSchema::build($into));
            $expected = $this->getTestData(Schema::class)->content('.graphql');

            $this->assertEquals($expected, $actual);
        } else {
            $this->markTestSkipped('DataLoader is disabled.');
        }
    }
}
