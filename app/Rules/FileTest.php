<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\Rules\File
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class FileTest extends TestCase {
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
            'ep.image.max_size' => 1,
            'ep.image.formats'  => [],
        ]);

        $rule   = $this->app->make(File::class);
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
                    'ep.file.max_size' => 100,
                    'ep.file.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.txt', 250),
            ],
            'too small'        => [
                true,
                [
                    'ep.file.max_size' => 1000,
                    'ep.file.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.txt', 250),
            ],
            'type does matter' => [
                false,
                [
                    'ep.file.max_size' => 1000,
                    'ep.file.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.exe', 250),
            ],
            'after all'        => [
                true,
                [
                    'ep.file.max_size' => 1000,
                    'ep.file.formats'  => ['txt', 'exe'],
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
