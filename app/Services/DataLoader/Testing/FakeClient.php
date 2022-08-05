<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\Concerns\WithData;
use App\Services\DataLoader\Testing\Exceptions\ClientException;
use Exception;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;

class FakeClient extends Client {
    use WithTestData;
    use WithData;

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
}
