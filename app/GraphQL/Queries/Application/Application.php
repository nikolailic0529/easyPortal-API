<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\App\Service;
use Illuminate\Config\Repository;

class Application {
    public function __construct(
        protected Repository $config,
        protected Service $service,
    ) {
        // empty
    }

    /**
     * @return array<string,mixed>
     */
    public function __invoke(): array {
        $name     = $this->config->get('app.name');
        $version  = $this->service->getVersion();
        $response = [
            'name'    => $name,
            'version' => $version,
        ];

        return $response;
    }
}
