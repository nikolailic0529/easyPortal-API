<?php declare(strict_types = 1);

namespace App;

use Illuminate\Support\Env;
use LogicException;
use PHPUnit\Framework\TestCase;

use function define;

/**
 * @internal
 * @coversDefaultClass \App\Setting
 */
class SettingTest extends TestCase {
    /**
     * @covers ::get
     */
    public function testGet(): void {
        define('TEST_CONST', 'TEST_CONST');

        $this->assertEquals('TEST_CONST', Setting::get('UNKNOWN', 'TEST_CONST'));

        Env::getRepository()->set('UNKNOWN', 'UNKNOWN');

        $this->assertEquals('UNKNOWN', Setting::get('UNKNOWN', 'TEST_CONST'));
    }

    /**
     * @covers ::get
     */
    public function testGetNotExists(): void {
        $this->expectExceptionObject(new LogicException('Setting not found.'));

        $this->assertEquals(null, Setting::get(__FUNCTION__));
    }
}
