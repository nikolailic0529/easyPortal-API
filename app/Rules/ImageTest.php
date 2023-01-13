<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\Rules\Image
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ImageTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     *
     * @param SettingsFactory $settingsFactory
     */
    public function testPasses(bool $expected, mixed $settingsFactory, mixed $value): void {
        $this->setSettings($settingsFactory);
        $this->setSettings([
            'ep.file.max_size' => 1,
            'ep.file.formats'  => [],
        ]);

        $rule   = $this->app->make(Image::class);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
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
            'empty value'      => [
                false,
                [],
                '',
            ],
        ];
    }
    // </editor-fold>
}
