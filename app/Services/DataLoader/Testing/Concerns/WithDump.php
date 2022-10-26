<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Concerns;

use App\Services\DataLoader\Testing\Exceptions\ClientException;
use Exception;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

use function implode;
use function json_encode;
use function sha1;

use const JSON_THROW_ON_ERROR;

trait WithDump {
    private ?TestData $data = null;

    public function getData(): ?TestData {
        return $this->data;
    }

    public function setData(?TestData $data): static {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function hasDump(string $selector, string $graphql, array $variables): bool {
        $path  = $this->getDumpPath($selector, $graphql, $variables);
        $exist = (bool) $this->getData()?->file($path)->isFile();

        return $exist;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function getDump(string $selector, string $graphql, array $variables): mixed {
        // Data?
        $data = $this->getData();

        if ($data === null) {
            return null;
        }

        // Load
        $path = $this->getDumpPath($selector, $graphql, $variables);
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
