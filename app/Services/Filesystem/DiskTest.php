<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Exception;
use Illuminate\Contracts\Filesystem\Factory;
use LogicException;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Filesystem\Disk
 */
class DiskTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::url
     *
     * @dataProvider dataProviderUrl
     *
     * @param array<mixed> $config
     */
    public function testUrl(string|Exception $expected, array $config, string $path): void {
        if ($expected instanceof Exception) {
            $this->expectException($expected::class);
            $this->expectExceptionMessage($expected->getMessage());
        }

        $name = $this->faker->uuid;

        $this->setSettings([
            "filesystems.disks.{$name}" => $config,
        ]);

        $this->assertEquals($expected, (new class($name, $this->app->make(Factory::class)) extends Disk {
            public function __construct(
                protected string $name,
                Factory $factory,
            ) {
                parent::__construct($factory);
            }

            public function getName(): string {
                return $this->name;
            }
        })->url($path));
    }

    /**
     * @covers ::isPublic
     * @dataProvider dataProviderIsPublic
     *
     * @param array<mixed> $config
     */
    public function testIsPublic(bool $expected, array $config): void {
        $name = $this->faker->uuid;

        $this->setSettings([
            "filesystems.disks.{$name}" => $config,
        ]);

        $this->assertEquals($expected, (new class($name, $this->app->make(Factory::class)) extends Disk {
            public function __construct(
                protected string $name,
                Factory $factory,
            ) {
                parent::__construct($factory);
            }

            public function getName(): string {
                return $this->name;
            }
        })->isPublic());
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
                    'Is not possible to get url for the file from non-public disk',
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
