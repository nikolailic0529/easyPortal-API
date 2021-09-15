<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Mockery;
use Tests\TestCase;

use function file_get_contents;
use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Storage
 */
class StorageTest extends TestCase {
    /**
     * @covers ::has
     */
    public function testGet(): void {
        $env = Mockery::mock(Storage::class);
        $env->makePartial();
        $env
            ->shouldReceive('load')
            ->times(3)
            ->andReturn(['TEST' => null, 'TEST2' => 123]);

        $this->assertNull($env->get('TEST'));
        $this->assertNull($env->get('UNKNOWN'));
        $this->assertEquals(123, $env->get('TEST2'));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $env = Mockery::mock(Storage::class);
        $env->makePartial();
        $env
            ->shouldReceive('load')
            ->twice()
            ->andReturn(['TEST' => null]);

        $this->assertTrue($env->has('TEST'));
        $this->assertFalse($env->has('UNKNOWN'));
    }

    /**
     * @covers ::load
     */
    public function testLoad(): void {
        $json    = $this->getTempFile(json_encode(['TEST' => 'value']))->getPathname();
        $storage = Mockery::mock(Storage::class);
        $storage->shouldAllowMockingProtectedMethods();
        $storage->makePartial();
        $storage
            ->shouldReceive('getPath')
            ->once()
            ->andReturn($json);

        $this->assertEquals(['TEST' => 'value'], $storage->load());
        $this->assertEquals(['TEST' => 'value'], $storage->load());
    }

    /**
     * @covers ::save
     */
    public function testSave(): void {
        $json    = $this->getTempFile(json_encode(['TEST' => 'value']))->getPathname();
        $storage = Mockery::mock(Storage::class);
        $storage->shouldAllowMockingProtectedMethods();
        $storage->makePartial();
        $storage
            ->shouldReceive('getPath')
            ->once()
            ->andReturn($json);

        $this->assertTrue($storage->save(['NEW' => 'value']));
        $this->assertEquals(['NEW' => 'value'], $storage->load());
        $this->assertEquals(
            json_encode(['NEW' => 'value'], JSON_PRETTY_PRINT),
            file_get_contents($json),
        );
    }
}
