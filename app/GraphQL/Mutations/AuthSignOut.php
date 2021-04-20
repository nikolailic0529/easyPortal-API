<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;

use RuntimeException;

use function sprintf;

class AuthSignOut {
    protected AuthManager $auth;
    protected Repository  $config;

    public function __construct(AuthManager $auth, Repository $config) {
        $this->auth   = $auth;
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): string {
        throw new RuntimeException('FIXME [KeyCloak] Not implemented.');

        $this->auth->logout();

        $url = sprintf(
            'https://%s/v2/logout?client_id=%s&returnTo=%s',
            $this->config->get('fixme.domain'),
            $this->config->get('fixme.client_id'),
            $this->config->get('app.url'),
        );

        return $url;
    }
}
