<?php declare(strict_types = 1);

namespace App\Services\Auth0;

use Auth0\SDK\API\Management as Api;
use Illuminate\Contracts\Config\Repository;

class Management {
    protected Api $management;

    public function __construct(Repository $config) {
        $token            = $config->get('laravel-auth0.api_token');
        $domain           = $config->get('laravel-auth0.domain');
        $guzzleOptions    = $config->get('laravel-auth0.guzzle_options', []);
        $this->management = new Api($token, $domain, $guzzleOptions);
    }

    /**
     * @see \Auth0\SDK\API\Management\Users::create()
     *
     * @param array<mixed> $data
     *
     * @return array{user_id: string, picture: string}
     */
    public function createUser(array $data): array {
        // FIXME [auth0] Specify connection
        $data['connection'] ??= 'Username-Password-Authentication';

        // FIXME [auth0] Tenant probably required here
        return $this->management->users()->create($data);
    }
}
