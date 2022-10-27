<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\Concerns\WithDump;
use App\Services\DataLoader\Testing\Concerns\WithLimit;

class FakeClient extends Client {
    use WithLimit;
    use WithDump;

    /**
     * @inheritdoc
     */
    protected function callExecute(string $selector, string $graphql, array $variables, array $files): mixed {
        return $this->getDump($selector, $graphql, $variables);
    }
}
