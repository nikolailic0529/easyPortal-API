<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Disks;

use App\Models\File;
use App\Models\Model;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

use function hash_file;
use function str_replace;

/**
 * @internal
 * @coversDefaultClass \App\Services\Filesystem\Disks\ModelDisk
 */
class ModelDiskTest extends TestCase {
    /**
     * @covers ::store
     */
    public function testStore(): void {
        $id    = $this->faker->uuid;
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

    /**
     * @covers ::storeToFile
     */
    public function testStoreToFile(): void {
        $id     = $this->faker->uuid;
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

        $this->assertInstanceOf(File::class, $actual);
        $this->assertTrue($actual->exists);
        $this->assertEquals($id, $actual->object_id);
        $this->assertEquals('abc', $actual->object_type);
        $this->assertEquals($upload->getClientOriginalName(), $actual->name);
        $this->assertEquals($upload->getSize(), $actual->size);
        $this->assertEquals($upload->getMimeType(), $actual->type);
        $this->assertEquals('disk', $actual->disk);
        $this->assertEquals('path/to/file.txt', $actual->path);
        $this->assertEquals(hash_file('sha256', $upload->getPathname()), $actual->hash);
    }
}
