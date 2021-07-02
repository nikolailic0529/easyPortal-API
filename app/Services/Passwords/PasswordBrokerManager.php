<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager as IlluminatePasswordBrokerManager;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

use function base64_decode;
use function is_null;
use function substr;

/**
 * We need to redefine this class because this is only one way to override
 * {@link \Illuminate\Auth\Passwords\TokenRepositoryInterface} instance.
 *
 * @mixin \Illuminate\Contracts\Auth\PasswordBroker
 *
 * @noinspection PhpHierarchyChecksInspection because base class uses `__call`
 *      to redirect calls into {@link \Illuminate\Contracts\Auth\PasswordBroker}
 *      implementation.
 */
class PasswordBrokerManager extends IlluminatePasswordBrokerManager {
    /**
     * @param array<mixed> $config
     */
    protected function createTokenRepository(array $config): TokenRepositoryInterface {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        $connection = $config['connection'] ?? null;

        return new TokenRepository(
            $this->app['db']->connection($connection),
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire'],
            $config['throttle'] ?? 0,
        );
    }

    /**
     * @inheritdoc
     */
    protected function resolve($name) {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        return new PasswordBroker(
            $this->createTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider'] ?? null),
        );
    }
}
