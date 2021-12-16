<?php

namespace Tests;

use LastDragon_ru\LaraASP\Testing\Database\QueryLog\QueryLog;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

use function file_put_contents;
use function json_encode;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @mixin \Tests\TestCase
 */
trait WithQueryLogs {
    use WithTestData;
    use WithQueryLog;

    protected function assertQueryLogEquals(string $expected, QueryLog $log, string $message = ''): void {
        $data     = $this->getTestData();
        $queries  = $this->cleanupQueryLog($log->get());
        $actual   = json_encode($queries, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        $expected = trim($data->content($expected));

        if ($expected === '') {
            self::assertNotFalse(file_put_contents($data->path($expected), $actual));
        } else {
            self::assertEquals($expected, $actual, $message);
        }
    }

    /**
     * @param array<array{query: string, bindings: array<mixed>, time: float|null}> $queries
     *
     * @return array<array{query: string, bindings: array<mixed>}>
     */
    protected function cleanupQueryLog(array $queries): array {
        foreach ($queries as &$query) {
            unset($query['time']);
        }

        return $queries;
    }
}
