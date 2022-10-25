<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Concerns;

use App\Services\DataLoader\Testing\Exceptions\ClientException;
use Exception;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

use function implode;
use function json_encode;
use function sha1;

use const JSON_THROW_ON_ERROR;

trait WithDump {
    use WithTestData;
    use WithData;

    /**
     * @param array<string, mixed> $variables
     */
    protected function hasDump(string $selector, string $graphql, array $variables): bool {
        $path  = $this->getDumpPath($selector, $graphql, $variables);
        $data  = $this->getTestData($this->getData());
        $exist = $data->file($path)->isFile();

        return $exist;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function getDump(string $selector, string $graphql, array $variables): mixed {
        $path = $this->getDumpPath($selector, $graphql, $variables);
        $data = $this->getTestData($this->getData());
        $json = null;

        try {
            $json = $data->json($path)['response'];
        } catch (Exception $exception) {
            $path  = $data->path($path);
            $error = new ClientException($path, $selector, $graphql, $variables, $exception);

            $this->handler->report($error);

            throw $error;
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function getDumpPath(string $selector, string $graphql, array $variables): string {
        // todo(DataLoader): Query normalization before hash calculation
        $dump = implode('.', [sha1($graphql), sha1(json_encode($variables, JSON_THROW_ON_ERROR)), 'json']);
        $path = "{$selector}/{$dump}";

        return $path;
    }
}
