<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Disks;

use App\Models\File;
use App\Utils\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use LogicException;
use Mockery;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

use function hash_file;
use function sprintf;
use function str_replace;

/**
 * @internal
 * @covers \App\Services\Filesystem\Disks\ModelDisk
 */
class ModelDiskTest extends TestCase {
    public function testStore(): void {
        $id    = $this->faker->uuid();
        $model = Mockery::mock(Model::class);
        $model
            ->shouldReceive('getKey')
            ->twice()
            ->andReturn($id);

        $upload = Mockery::mock(UploadedFile::class);
        $upload
            ->shouldReceive('storePublicly')
            ->with(str_replace('-', '/', $id), $id)
            ->once()
            ->andReturn('/path/public');
        $upload
            ->shouldReceive('store')
            ->with(str_replace('-', '/', $id), $id)
            ->once()
            ->andReturn('/path/private');

        $disk = Mockery::mock(ModelDisk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('getName')
            ->twice()
            ->andReturn($id);
        $disk
            ->shouldReceive('getModel')
            ->twice()
            ->andReturn($model);
        $disk
            ->shouldReceive('isPublic')
            ->twice()
            ->andReturn(true, false);

        $disk->store($upload);
        $disk->store($upload);
    }

    public function testStoreToFile(): void {
        $id     = $this->faker->uuid();
        $upload = UploadedFile::fake()->create('test.txt');
        $model  = Mockery::mock(Model::class);
        $model
            ->shouldReceive('getKey')
            ->once()
            ->andReturn($id);
        $model
            ->shouldReceive('getMorphClass')
            ->once()
            ->andReturn('abc');

        $disk = Mockery::mock(ModelDisk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('getName')
            ->once()
            ->andReturn('disk');
        $disk
            ->shouldReceive('getModel')
            ->twice()
            ->andReturn($model);
        $disk
            ->shouldReceive('store')
            ->once()
            ->andReturn('path/to/file.txt');

        $actual = $disk->storeToFile($upload);

        self::assertInstanceOf(File::class, $actual);
        self::assertTrue($actual->exists);
        self::assertEquals($id, $actual->object_id);
        self::assertEquals('abc', $actual->object_type);
        self::assertEquals($upload->getClientOriginalName(), $actual->name);
        self::assertEquals($upload->getSize(), $actual->size);
        self::assertEquals($upload->getMimeType(), $actual->type);
        self::assertEquals('disk', $actual->disk);
        self::assertEquals('path/to/file.txt', $actual->path);
        self::assertEquals(hash_file('sha256', $upload->getPathname()), $actual->hash);
    }

    public function testStoreToFiles(): void {
        $a    = Mockery::mock(UploadedFile::class);
        $b    = Mockery::mock(UploadedFile::class);
        $disk = Mockery::mock(ModelDisk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('storeToFile')
            ->with($a)
            ->once()
            ->andReturn(new File());
        $disk
            ->shouldReceive('storeToFile')
            ->with($b)
            ->once()
            ->andReturn(new File());

        self::assertCount(2, $disk->storeToFiles([$a, $b]));
    }

    public function testDownloadPath(): void {
        $response = Mockery::mock(StreamedResponse::class);
        $path     = 'path/to/file';
        $fs       = Mockery::mock(FilesystemAdapter::class);
        $fs
            ->shouldReceive('download')
            ->with($path, null, [])
            ->once()
            ->andReturn($response);

        $disk = Mockery::mock(ModelDisk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('filesystem')
            ->once()
            ->andReturn($fs);

        self::assertSame($response, $disk->download($path));
    }

    public function testDownloadFile(): void {
        $response = Mockery::mock(StreamedResponse::class);
        $file     = File::factory()->create();
        $fs       = Mockery::mock(FilesystemAdapter::class);
        $fs
            ->shouldReceive('download')
            ->with($file->path, $file->name, [])
            ->once()
            ->andReturn($response);

        $disk = Mockery::mock(ModelDisk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('getName')
            ->once()
            ->andReturn($file->disk);
        $disk
            ->shouldReceive('filesystem')
            ->once()
            ->andReturn($fs);

        self::assertSame($response, $disk->download($file));
    }

    public function testDownloadFileFromAnotherDisc(): void {
        $file = File::factory()->create();
        $fs   = Mockery::mock(FilesystemAdapter::class);
        $fs
            ->shouldReceive('download')
            ->never();

        $disk = Mockery::mock(ModelDisk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('getName')
            ->twice()
            ->andReturn('another');
        $disk
            ->shouldReceive('filesystem')
            ->never();

        self::expectExceptionObject(new LogicException(sprintf(
            'File should be from `%s` disk, but it is from `%s` disk.',
            'another',
            $file->disk,
        )));

        $disk->download($file);
    }
}
