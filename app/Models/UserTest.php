<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Enums\UserType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\User
 */
class UserTest extends TestCase {
    /**
     * @covers ::isRoot
     */
    public function testIsRoot(): void {
        foreach (UserType::getValues() as $type) {
            $this->assertEquals($type === UserType::local(), User::factory()->make([
                'type' => $type,
            ]));
        }
    }
}
