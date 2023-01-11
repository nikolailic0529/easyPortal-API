<?php declare(strict_types = 1);

namespace App\Rules;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\CronExpression
 */
class CronExpressionTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        self::assertEquals($expected, (new CronExpression())->passes('test', $value));
    }

    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.cron' => 'message validation.cron',
                ],
            ];
        });

        self::assertEquals('message validation.cron', (new CronExpression())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            // Basic
            '* * * *'       => [false, '* * * *'],
            '* * * * *'     => [true, '* * * * *'],
            '* * * * * * '  => [false, '* * * * * *'],
            // Minutes
            '0 * * * *'     => [true, '0 * * * *'],
            '1 * * * *'     => [true, '1 * * * *'],
            '1,2,3 * * * *' => [true, '1,2,3 * * * *'],
            '1-10 * * * *'  => [true, '1-10 * * * *'],
            '*/2 * * * *'   => [true, '*/2 * * * *'],
            '*-2 * * * *'   => [false, '*-2 * * * *'],
            '*,2 * * * *'   => [true, '*,2 * * * *'],
            '60 * * * *'    => [false, '60 * * * *'],
            // Hour
            '* 0 * * * *'   => [true, '* 0 * * *'],
            '* 1 * * * *'   => [true, '* 1 * * *'],
            '* 1,2,3 * * *' => [true, '* 1,2,3 * * *'],
            '* 1-10 * * *'  => [true, '* 1-10 * * *'],
            '* */2 * * *'   => [true, '* */2 * * *'],
            '* *-2 * * *'   => [false, '* *-2 * * *'],
            '* *,2 * * *'   => [true, '* *,2 * * *'],
            '* 25 * * *'    => [false, '* 25 * * *'],
            // Day
            '* * 0 * * *'   => [false, '* * 0 * *'],
            '* * 1 * * *'   => [true, '* * 1 * *'],
            '* * 1,2,3 * *' => [true, '* * 1,2,3 * *'],
            '* * 1-10 * *'  => [true, '* * 1-10 * *'],
            '* * */2 * *'   => [true, '* * */2 * *'],
            '* * *-2 * *'   => [false, '* * *-2 * *'],
            '* * *,2 * *'   => [true, '* * *,2 * *'],
            '* * 32 * *'    => [false, '* * 32 * *'],
            // Month
            '* * * 0 * *'   => [false, '* * * 0 *'],
            '* * * 1 * *'   => [true, '* * * 1 *'],
            '* * * 1,2,3 *' => [true, '* * * 1,2,3 *'],
            '* * * 1-10 *'  => [true, '* * * 1-10 *'],
            '* * * */2 *'   => [true, '* * * */2 *'],
            '* * * *-2 *'   => [false, '* * * *-2 *'],
            '* * * *,2 *'   => [true, '* * * *,2 *'],
            '* * * 13 *'    => [false, '* * * 13 *'],
            // Day of week
            '* * * * 0 *'   => [true, '* * * * 0'],
            '* * * * 1 *'   => [true, '* * * * 1'],
            '* * * * 1,2,3' => [true, '* * * * 1,2,3'],
            '* * * * 1-7'   => [true, '* * * * 0-7'],
            '* * * * */2'   => [true, '* * * * */2'],
            '* * * * *-2'   => [false, '* * * * *-2'],
            '* * * * *,2'   => [true, '* * * * *,2'],
            '* * * * 8'     => [false, '* * * * 8'],
        ];
    }
    // </editor-fold>
}
