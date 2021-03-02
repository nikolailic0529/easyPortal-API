<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client
 */
class ClientTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testEndpoint(): void {
        $this->markTestIncomplete('This test should download the GraphQL and compared it with the supported.');
    }
}
