<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Concerns;

use function implode;
use function json_encode;
use function sha1;

use const JSON_THROW_ON_ERROR;

trait WithDump {
    /**
     * @param array<string, mixed> $variables
     */
    protected function callDumpPath(string $selector, string $graphql, array $variables): string {
        // todo(DataLoader): Query normalization before hash calculation
        $dump = implode('.', [sha1($graphql), sha1(json_encode($variables, JSON_THROW_ON_ERROR)), 'json']);
        $path = "{$selector}/{$dump}";

        return $path;
    }
}
