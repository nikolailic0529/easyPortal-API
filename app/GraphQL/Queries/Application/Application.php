<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Maintenance\ApplicationInfo;
use Illuminate\Config\Repository;

class Application {
    public function __construct(
        protected Repository $config,
        protected ApplicationInfo $info,
    ) {
        // empty
    }

    /**
     * @return array<string,mixed>
     */
    public function __invoke(): array {
        $name     = $this->config->get('app.name');
        $version  = $this->info->getVersion();
        $response = [
            'name'    => $name,
            'version' => $version,
        ];

        return $response;
    }
}
