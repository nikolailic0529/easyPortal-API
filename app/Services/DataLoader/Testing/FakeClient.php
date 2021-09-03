<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Services\DataLoader\Client\Client;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

class FakeClient extends Client {
    use WithTestData;

    /**
     * @var class-string<\App\Services\DataLoader\Testing\Data\Data>
     */
    private string $data;

    /**
     * @return class-string<\App\Services\DataLoader\Testing\Data\Data>
     */
    public function getData(): string {
        return $this->data;
    }

    /**
     * @param class-string<\App\Services\DataLoader\Testing\Data\Data> $data
     */
    public function setData(string $data): static {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array<mixed>  $params
     * @param array<string> $files
     */
    protected function callExecute(string $selector, string $graphql, array $params, array $files): mixed {
        $path = $this->callDumpPath($selector, $graphql, $params);
        $data = $this->getTestData($this->getData());
        $dump = $data->file($path);
        $json = $dump->isFile() && $dump->isReadable()
            ? $data->json($path)['response']
            : [];

        return $json;
    }

    protected function callDump(string $selector, string $graphql, mixed $params, mixed $json): void {
        // empty
    }
}