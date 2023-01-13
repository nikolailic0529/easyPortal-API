<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Exceptions\StorageFileCorrupted;
use Tests\TestCase;

use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * @internal
 * @covers \App\Services\Filesystem\JsonStorage
 */
class JsonStorageTest extends TestCase {
    public function testDelete(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        self::assertFalse($fs->exists($file));
        self::assertTrue($fs->put($file, json_encode([])));
        self::assertTrue($fs->exists($file));
        self::assertTrue($storage->delete());
        self::assertFalse($fs->exists($file));
    }

    public function testDeleteCorrupted(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        self::expectException(StorageFileCorrupted::class);

        self::assertTrue($fs->put($file, 'invalid json'));
        self::assertTrue($storage->delete());
    }

    public function testDeleteCorruptedForce(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        self::assertTrue($fs->put($file, 'invalid json'));
        self::assertTrue($storage->delete(true));
    }

    public function testLoad(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        self::assertTrue($fs->put($file, json_encode(['key' => 'value'])));
        self::assertEquals(['key' => 'value'], $storage->load());
    }

    public function testLoadCorrupted(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        self::expectException(StorageFileCorrupted::class);

        self::assertTrue($fs->put($file, 'invalid json'));
        self::assertEquals(['key' => 'value'], $storage->load());
    }

    public function testSave(): void {
        $disc    = $this->app->make(AppDisk::class);
        $storage = new JsonStorageTest_Storage($disc);
        $file    = $storage->getFile();
        $fs      = $disc->filesystem();

        self::assertTrue($storage->save(['key' => 'value']));
        self::assertEquals(json_encode(['key' => 'value'], JSON_PRETTY_PRINT), $fs->get($file));
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
