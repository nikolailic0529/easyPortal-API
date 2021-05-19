<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\DispatchApplicationServiceFailed;
use App\GraphQL\Mutations\DispatchApplicationServiceNotFoundException;
use App\Http\Controllers\ExportGraphQLQueryInvalid;
use App\Services\Filesystem\StorageFileCorrupted as FilesystemStorageFileCorrupted;
use App\Services\Filesystem\StorageFileDeleteFailed as FilesystemStorageFileDeleteFailed;
use App\Services\Filesystem\StorageFileSaveFailed as FilesystemStorageFileSaveFailed;
use App\Services\KeyCloak\Exceptions\AnotherUserExists as KeyCloakAnotherUserExists;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed as KeyCloakAuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InsufficientData as KeyCloakInsufficientData;
use App\Services\KeyCloak\Exceptions\InvalidCredentials as KeyCloakInvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity as KeyCloakInvalidIdentity;
use App\Services\KeyCloak\Exceptions\StateMismatch as KeyCloakStateMismatch;
use App\Services\KeyCloak\Exceptions\UnknownScope as KeyCloakUnknownScope;
use App\Services\Organization\Exceptions\UnknownOrganization as OrganizationUnknownOrganization;
use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv as SettingsSettingsFailedToLoadEnv;
use App\Services\Tokens\Exceptions\InvalidCredentials as TokensInvalidCredentials;
use Throwable;

class ErrorCodes {
    /**
     * @var array<class-string<\Throwable>,string|int>
     */
    protected static array $map = [
        DispatchApplicationServiceNotFoundException::class => 'ERR01',
        DispatchApplicationServiceFailed::class            => 'ERR02',
        FilesystemStorageFileCorrupted::class              => 'ERR03',
        FilesystemStorageFileDeleteFailed::class           => 'ERR04',
        FilesystemStorageFileSaveFailed::class             => 'ERR05',
        SettingsSettingsFailedToLoadEnv::class             => 'ERR06',
        ExportGraphQLQueryInvalid::class                   => 'ERR07',
        KeyCloakAuthorizationFailed::class                 => 'ERR08',
        KeyCloakStateMismatch::class                       => 'ERR09',
        KeyCloakInvalidIdentity::class                     => 'ERR10',
        KeyCloakInvalidCredentials::class                  => 'ERR11',
        KeyCloakInsufficientData::class                    => 'ERR12',
        OrganizationUnknownOrganization::class             => 'ERR13',
        KeyCloakUnknownScope::class                        => 'ERR14',
        KeyCloakAnotherUserExists::class                   => 'ERR15',
        TokensInvalidCredentials::class                    => 'ERR16',
    ];

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
