<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\Data\Data;
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
     * @param array<mixed>  $params
     * @param array<string> $files
     */
    protected function callExecute(string $selector, string $graphql, array $params, array $files): mixed {
        $path = $this->callDumpPath($selector, $graphql, $params);
        $data = $this->getTestData($this->getData());
        $json = $data->json($path)['response'];

        return $json;
    }

    protected function callDump(string $selector, string $graphql, mixed $params, mixed $json): void {
        // empty
    }
}
