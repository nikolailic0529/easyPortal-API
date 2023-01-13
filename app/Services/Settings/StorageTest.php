<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Mockery;
use Tests\TestCase;

use function file_get_contents;
use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * @internal
 * @covers \App\Services\Settings\Storage
 */
class StorageTest extends TestCase {
    public function testGet(): void {
        $env = Mockery::mock(Storage::class);
        $env->makePartial();
        $env
            ->shouldReceive('load')
            ->times(3)
            ->andReturn(['TEST' => null, 'TEST2' => 123]);

        self::assertNull($env->get('TEST'));
        self::assertNull($env->get('UNKNOWN'));
        self::assertEquals(123, $env->get('TEST2'));
    }

    public function testHas(): void {
        $env = Mockery::mock(Storage::class);
        $env->makePartial();
        $env
            ->shouldReceive('load')
            ->twice()
            ->andReturn(['TEST' => null]);

        self::assertTrue($env->has('TEST'));
        self::assertFalse($env->has('UNKNOWN'));
    }

    public function testLoad(): void {
        $json    = $this->getTempFile(json_encode(['TEST' => 'value']))->getPathname();
        $storage = Mockery::mock(Storage::class);
        $storage->shouldAllowMockingProtectedMethods();
        $storage->makePartial();
        $storage
            ->shouldReceive('getPath')
            ->once()
            ->andReturn($json);

        self::assertEquals(['TEST' => 'value'], $storage->load());
        self::assertEquals(['TEST' => 'value'], $storage->load());
    }

    public function testSave(): void {
        $json    = $this->getTempFile(json_encode(['TEST' => 'value']))->getPathname();
        $storage = Mockery::mock(Storage::class);
        $storage->shouldAllowMockingProtectedMethods();
        $storage->makePartial();
        $storage
            ->shouldReceive('getPath')
            ->once()
            ->andReturn($json);

        self::assertTrue($storage->save(['NEW' => 'value']));
        self::assertEquals(['NEW' => 'value'], $storage->load());
        self::assertEquals(
            json_encode(['NEW' => 'value'], JSON_PRETTY_PRINT),
            file_get_contents($json),
        );
    }

    public function testDelete(): void {
        $storage = Mockery::mock(Storage::class);
        $storage->shouldAllowMockingProtectedMethods();
        $storage->makePartial();
        $storage
            ->shouldReceive('save')
            ->with([])
            ->once()
            ->andReturn(true);

        $storage->delete();
    }
}
