<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Exceptions\StorageFileCorrupted;
use Tests\TestCase;

use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * @internal
 * @coversDefaultClass \App\Services\Filesystem\JsonStorage
 */
class JsonStorageTest extends TestCase {
    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        $this->assertFalse($fs->exists($file));
        $this->assertTrue($fs->put($file, json_encode([])));
        $this->assertTrue($fs->exists($file));
        $this->assertTrue($storage->delete());
        $this->assertFalse($fs->exists($file));
    }

    /**
     * @covers ::delete
     */
    public function testDeleteCorrupted(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        $this->expectException(StorageFileCorrupted::class);

        $this->assertTrue($fs->put($file, 'invalid json'));
        $this->assertTrue($storage->delete());
    }

    /**
     * @covers ::delete
     */
    public function testDeleteCorruptedForce(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        $this->assertTrue($fs->put($file, 'invalid json'));
        $this->assertTrue($storage->delete(true));
    }

    /**
     * @covers ::load
     */
    public function testLoad(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        $this->assertTrue($fs->put($file, json_encode(['key' => 'value'])));
        $this->assertEquals(['key' => 'value'], $storage->load());
    }

    /**
     * @covers ::load
     */
    public function testLoadCorrupted(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        $this->expectException(StorageFileCorrupted::class);

        $this->assertTrue($fs->put($file, 'invalid json'));
        $this->assertEquals(['key' => 'value'], $storage->load());
    }

    /**
     * @covers ::save
     */
    public function testSave(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        $this->assertTrue($storage->save(['key' => 'value']));
        $this->assertEquals(json_encode(['key' => 'value'], JSON_PRETTY_PRINT), $fs->get($file));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JsonStorageTest_Storage extends JsonStorage {
    public function __construct(Disk $disc) {
        parent::__construct($disc, 'storage.json');
    }

    public function getDisk(): Disk {
        return parent::getDisk();
    }

    public function getFile(): string {
        return parent::getFile();
    }
}
