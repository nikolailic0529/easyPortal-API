<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\Utils\Group
 */
class GroupTest extends TestCase {
    /**
     * @covers ::update
     */
    public function testUpdate(): void {
        $row   = 0;
        $group = new Group(0);

        self::assertSame($group, $group->update($row++, null));
        self::assertSame($group, $group->update($row++, null));
        self::assertSame($group, $group->update($row++, null));
        self::assertEquals(0, $group->getStartRow());
        self::assertEquals($row - 1, $group->getEndRow());

        $groupAEnd = $group->getEndRow();
        $groupA    = $group->update($row++, true);

        self::assertNotSame($group, $groupA);
        self::assertEquals(0, $groupA->getStartRow());
        self::assertEquals($groupAEnd, $groupA->getEndRow());
        self::assertEquals($groupAEnd + 1, $group->getStartRow());
        self::assertEquals($groupAEnd + 1, $group->getEndRow());

        self::assertSame($group, $group->update($row++, true));
        self::assertSame($group, $group->update($row++, true));
        self::assertSame($group, $group->update($row++, true));
        self::assertEquals($groupAEnd + 1, $group->getStartRow());
        self::assertEquals($row - 1, $group->getEndRow());

        $groupBEnd = $group->getEndRow();
        $groupB    = $group->update($row++, false);

        self::assertNotSame($group, $groupB);
        self::assertEquals($groupAEnd + 1, $groupB->getStartRow());
        self::assertEquals($groupBEnd, $groupB->getEndRow());
    }

    /**
     * @covers ::move
     */
    public function testMove(): void {
        $group = new Group(0);

        self::assertEquals(0, $group->getStartRow());
        self::assertEquals(0, $group->getEndRow());

        $group->move(5);

        self::assertEquals(5, $group->getStartRow());
        self::assertEquals(5, $group->getEndRow());
    }
}
