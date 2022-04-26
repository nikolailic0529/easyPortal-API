<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\Spreadsheet
 */
class SpreadsheetTest extends TestCase {
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

        $rule   = $this->app->make(Spreadsheet::class);
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
                ],
                UploadedFile::fake()->create('text.xlsx', 150),
            ],
            'too small'        => [
                true,
                [
                    'ep.file.max_size' => 250,
                ],
                UploadedFile::fake()->create('text.xlsx', 150),
            ],
            'type does matter' => [
                false,
                [
                    'ep.file.max_size' => 250,
                    'ep.file.formats'  => ['txt'],
                ],
                UploadedFile::fake()->create('text.txt', 150),
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
