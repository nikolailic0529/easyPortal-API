<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\DispatchApplicationServiceFailed;
use App\GraphQL\Mutations\DispatchApplicationServiceNotFoundException;
use App\Http\Controllers\ExportGraphQLQueryInvalid;
use App\Services\Filesystem\StorageFileCorrupted;
use App\Services\Filesystem\StorageFileDeleteFailed;
use App\Services\Filesystem\StorageFileSaveFailed;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InsufficientData;
use App\Services\KeyCloak\Exceptions\InvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity;
use App\Services\KeyCloak\Exceptions\StateMismatch;
use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv;
use App\Services\Tenant\Exceptions\UnknownTenant;
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
        ExportGraphQLQueryInvalid::class                   => 'ERR07',
        AuthorizationFailed::class                         => 'ERR08',
        StateMismatch::class                               => 'ERR09',
        InvalidIdentity::class                             => 'ERR10',
        InvalidCredentials::class                          => 'ERR11',
        InsufficientData::class                            => 'ERR12',
        UnknownTenant::class                               => 'ERR13',
    ];

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
