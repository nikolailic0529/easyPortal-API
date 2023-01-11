<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use LogicException;
use Mockery;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Filesystem\Disk
 */
class DiskTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderUrl
     *
     * @param array<mixed> $config
     */
    public function testUrl(string|Exception $expected, array $config, string $path): void {
        if ($expected instanceof Exception) {
            self::expectException($expected::class);
            self::expectExceptionMessage($expected->getMessage());
        }

        $name = $this->faker->uuid();
        $disk = new class(
            $this->app->make(Factory::class),
            $this->app->make(Repository::class),
            $name,
        ) extends Disk {
            public function __construct(
                Factory $factory,
                Repository $config,
                protected string $name,
            ) {
                parent::__construct($factory, $config);
            }

            public function getName(): string {
                return $this->name;
            }
        };

        $this->setSettings([
            "filesystems.disks.{$name}" => $config,
        ]);

        self::assertEquals($expected, $disk->url($path));
    }

    /**
     * @dataProvider dataProviderIsPublic
     *
     * @param array<mixed> $config
     */
    public function testIsPublic(bool $expected, array $config): void {
        $name = $this->faker->uuid();
        $disk = new class(
            $this->app->make(Factory::class),
            $this->app->make(Repository::class),
            $name,
        ) extends Disk {
            public function __construct(
                Factory $factory,
                Repository $config,
                protected string $name,
            ) {
                parent::__construct($factory, $config);
            }

            public function getName(): string {
                return $this->name;
            }
        };

        $this->setSettings([
            "filesystems.disks.{$name}" => $config,
        ]);

        self::assertEquals($expected, $disk->isPublic());
    }

    public function testDownload(): void {
        $response = Mockery::mock(StreamedResponse::class);
        $path     = 'path/to/file';
        $fs       = Mockery::mock(FilesystemAdapter::class);
        $fs
            ->shouldReceive('download')
            ->with($path, null, [])
            ->once()
            ->andReturn($response);

        $disk = Mockery::mock(Disk::class);
        $disk->makePartial();
        $disk
            ->shouldReceive('filesystem')
            ->once()
            ->andReturn($fs);

        self::assertSame($response, $disk->download($path));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderUrl(): array {
        return [
            'public with url'    => [
                'https://example.com/path/to/file/test.txt',
                [
                    'driver'     => 'local',
                    'root'       => 'test',
                    'url'        => 'https://example.com/path/to/file',
                    'visibility' => 'public',
                ],
                'test.txt',
            ],
            'public without url' => [
                '/storage/test.txt',
                [
                    'driver'     => 'local',
                    'root'       => 'test',
                    'visibility' => 'public',
                ],
                'test.txt',
            ],
            'private'            => [
                new LogicException(
                    'It is not possible to get url for the file from non-public disk',
                ),
                [
                    'driver' => 'local',
                    'root'   => 'test',
                ],
                'test.txt',
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderIsPublic(): array {
        return [
            'public'  => [
                true,
                [
                    'driver'     => 'local',
                    'root'       => 'test',
                    'visibility' => 'public',
                ],
            ],
            'private' => [
                false,
                [
                    'driver' => 'local',
                    'root'   => 'test',
                ],
            ],
        ];
    }
    // </editor-fold>
}
