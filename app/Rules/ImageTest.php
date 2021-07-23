<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Image
 */
class ImageTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     *
     * @param array<string,mixed> $settings
     */
    public function testPasses(bool $expected, array $settings, mixed $value): void {
        $this->setSettings($settings);
        $this->setSettings([
            'ep.file.max_size' => 1,
            'ep.file.formats'  => [],
        ]);

        $this->assertEquals($expected, $this->app->make(Image::class)->passes('test', $value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'too big'          => [
                false,
                [
                    'ep.image.max_size' => 100,
                    'ep.image.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.txt', 250),
            ],
            'too small'        => [
                true,
                [
                    'ep.image.max_size' => 1000,
                    'ep.image.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.txt', 250),
            ],
            'type does matter' => [
                false,
                [
                    'ep.image.max_size' => 1000,
                    'ep.image.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.exe', 250),
            ],
            'after all'        => [
                true,
                [
                    'ep.image.max_size' => 1000,
                    'ep.image.formats'  => ['txt', 'exe'],
                ],
                UploadedFile::fake()->create('text.exe', 250),
            ],
        ];
    }
    // </editor-fold>
}
