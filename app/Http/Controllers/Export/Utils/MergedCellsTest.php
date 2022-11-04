<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\Utils\MergedCells
 */
class MergedCellsTest extends TestCase {
    /**
     * @covers ::merge
     */
    public function testMerge(): void {
        $row    = 0;
        $merged = new MergedCells(0);

        self::assertNull($merged->merge($row++, null));
        self::assertNull($merged->merge($row++, null));
        self::assertNull($merged->merge($row++, null));
        self::assertEquals(0, $merged->getStartRow());
        self::assertEquals($row - 1, $merged->getEndRow());

        $groupAEnd = $merged->getEndRow();
        $groupA    = $merged->merge($row++, true);

        self::assertNotNull($groupA);
        self::assertEquals(0, $groupA->getStartRow());
        self::assertEquals($groupAEnd, $groupA->getEndRow());
        self::assertEquals($groupAEnd + 1, $merged->getStartRow());
        self::assertEquals($groupAEnd + 1, $merged->getEndRow());

        self::assertNull($merged->merge($row++, true));
        self::assertNull($merged->merge($row++, true));
        self::assertNull($merged->merge($row++, true));
        self::assertEquals($groupAEnd + 1, $merged->getStartRow());
        self::assertEquals($row - 1, $merged->getEndRow());

        $groupBEnd = $merged->getEndRow();
        $groupB    = $merged->merge($row++, false);

        self::assertNotNull($groupB);
        self::assertEquals($groupAEnd + 1, $groupB->getStartRow());
        self::assertEquals($groupBEnd, $groupB->getEndRow());
    }
}
