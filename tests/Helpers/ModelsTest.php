<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \Tests\Helpers\Models
 */
class ModelsTest extends TestCase {
    /**
     * @covers ::get
     */
    public function testGet(): void {
        self::assertNotEmpty(Models::get());
    }
}
