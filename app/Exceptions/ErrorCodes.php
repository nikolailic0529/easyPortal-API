<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\DispatchApplicationServiceFailed;
use App\GraphQL\Mutations\DispatchApplicationServiceNotFoundException;
use App\Services\Filesystem\StorageFileCorrupted;
use App\Services\Filesystem\StorageFileDeleteFailed;
use App\Services\Filesystem\StorageFileSaveFailed;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity;
use App\Services\KeyCloak\Exceptions\StateMismatch;
use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv;
use Throwable;

class ErrorCodes {
    /**
     * @var array<class-string<\Throwable>,string|int>
     */
    protected static array $map = [
        DispatchApplicationServiceNotFoundException::class => 'ERR01',
        DispatchApplicationServiceFailed::class            => 'ERR02',
        StorageFileCorrupted::class                        => 'ERR03',
        StorageFileDeleteFailed::class                     => 'ERR04',
        StorageFileSaveFailed::class                       => 'ERR05',
        SettingsFailedToLoadEnv::class                     => 'ERR06',
        AuthorizationFailed::class                         => 'ERR07',
        StateMismatch::class                               => 'ERR08',
        InvalidIdentity::class                             => 'ERR09',
        InvalidCredentials::class                          => 'ERR10',
    ];

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
