<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\Data\Data;
use App\Services\DataLoader\Testing\Exceptions\ClientException;
use Exception;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

class FakeClient extends Client {
    use WithTestData;

    /**
     * @var class-string<Data>
     */
    private string $data;

    /**
     * @return class-string<Data>
     */
    public function getData(): string {
        return $this->data;
    }

    /**
     * @param class-string<Data> $data
     */
    public function setData(string $data): static {
        $this->data = $data;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function callExecute(string $selector, string $graphql, array $variables, array $files): mixed {
        $path = $this->callDumpPath($selector, $graphql, $variables);
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

    protected function callDump(string $selector, string $graphql, mixed $variables, mixed $json): void {
        // empty
    }
}
