<?php declare(strict_types = 1);

namespace App\Services\I18n\Storages;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\I18n\Storages\Spreadsheet
 */
class SpreadsheetTest extends TestCase {
    public function testLoad(): void {
        $file = $this->getTestData()->file('.xlsx');
        $data = (new Spreadsheet($file))->load();

        self::assertEquals(
            [
                'a'   => 'aa',
                'a.a' => 'aaa',
                'b'   => 'bb',
                'c'   => '',
                'd'   => '123',
                'e'   => '123.45',
                'f'   => <<<'TEXT'
                    Line a

                    Line b
                    TEXT
                ,
            ],
            $data,
        );
    }
}
