<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Tests\TestCase;

/**
 * @internal
 * @covers \Tests\Helpers\Models
 */
class ModelsTest extends TestCase {
    public function testGet(): void {
        self::assertNotEmpty(Models::get());
    }
}
