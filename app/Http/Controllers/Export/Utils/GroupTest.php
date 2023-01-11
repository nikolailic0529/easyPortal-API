<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Utils\Group
 */
class GroupTest extends TestCase {
    public function testUpdate(): void {
        $row   = 0;
        $group = new Group();

        self::assertNull($group->update($row++, null));
        self::assertNull($group->update($row++, null));
        self::assertNull($group->update($row++, null));
        self::assertEquals(0, $group->getStartRow());
        self::assertEquals($row - 1, $group->getEndRow());

        $groupAEnd = $group->getEndRow();
        $groupA    = $group->update($row++, true);

        self::assertNotNull($groupA);
        self::assertEquals(0, $groupA->getStartRow());
        self::assertEquals($groupAEnd, $groupA->getEndRow());
        self::assertEquals($groupAEnd + 1, $group->getStartRow());
        self::assertEquals($groupAEnd + 1, $group->getEndRow());

        self::assertNull($group->update($row++, true));
        self::assertNull($group->update($row++, true));
        self::assertNull($group->update($row++, true));
        self::assertEquals($groupAEnd + 1, $group->getStartRow());
        self::assertEquals($row - 1, $group->getEndRow());

        $groupBEnd = $group->getEndRow();
        $groupB    = $group->update($row++, false);

        self::assertNotNull($groupB);
        self::assertEquals($groupAEnd + 1, $groupB->getStartRow());
        self::assertEquals($groupBEnd, $groupB->getEndRow());
    }

    public function testMove(): void {
        $group = new Group();

        self::assertEquals(0, $group->getStartRow());
        self::assertEquals(0, $group->getEndRow());

        $group->move(5);

        self::assertEquals(5, $group->getStartRow());
        self::assertEquals(5, $group->getEndRow());
    }

    public function testExpand(): void {
        $group = new Group();

        self::assertEquals(0, $group->getStartRow());
        self::assertEquals(0, $group->getEndRow());

        $group->expand(5);

        self::assertEquals(0, $group->getStartRow());
        self::assertEquals(5, $group->getEndRow());
    }

    public function testEnd(): void {
        $group = new Group(null);

        self::assertNull($group->end(1, null));
        self::assertNull($group->update(2, null));

        $groupA = $group->end(3, 'a');

        self::assertNotNull($groupA);
        self::assertEquals(1, $groupA->getStartRow());
        self::assertEquals(2, $groupA->getEndRow());
        self::assertEquals(3, $group->getStartRow());
        self::assertEquals(3, $group->getEndRow());
    }
}
