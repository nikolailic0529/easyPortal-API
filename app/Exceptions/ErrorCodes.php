<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\Auth\ResetPasswordSamePasswordException;
use App\GraphQL\Mutations\Auth\SignUpByInviteAlreadyUsed;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvalidToken;
use App\GraphQL\Mutations\Me\UpdateMePasswordInvalidCurrentPassword;
use App\GraphQL\Mutations\Org\DisableOrgUserInvalidUser;
use App\GraphQL\Mutations\Org\EnableOrgUserInvalidUser;
use App\GraphQL\Mutations\Org\ResetOrgUserPasswordInvalidUser;
use App\Http\Controllers\ExportGraphQLQueryEmpty;
use App\Http\Controllers\ExportGraphQLQueryInvalid;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled as DataLoaderDataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable as DataLoaderDataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed as DataLoaderGraphQLRequestFailed;
use App\Services\Filesystem\Exceptions\StorageFileCorrupted as FilesystemStorageFileCorrupted;
use App\Services\Filesystem\Exceptions\StorageFileDeleteFailed as FilesystemStorageFileDeleteFailed;
use App\Services\Filesystem\Exceptions\StorageFileSaveFailed as FilesystemStorageFileSaveFailed;
use App\Services\KeyCloak\Client\Exceptions\InvalidSettingClientUuid;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled;
use App\Services\KeyCloak\Client\Exceptions\RealmGroupUnknown;
use App\Services\KeyCloak\Client\Exceptions\RealmUserAlreadyExists as KeyCloakRealmUserAlreadyExists;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound as KeyCloakRealmUserNotFound;
use App\Services\KeyCloak\Client\Exceptions\RequestFailed as KeyCloakRequestFailed;
use App\Services\KeyCloak\Exceptions\AnotherUserExists as KeyCloakAnotherUserExists;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed as KeyCloakAuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InsufficientData as KeyCloakInsufficientData;
use App\Services\KeyCloak\Exceptions\InvalidCredentials as KeyCloakInvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity as KeyCloakInvalidIdentity;
use App\Services\KeyCloak\Exceptions\StateMismatch as KeyCloakStateMismatch;
use App\Services\KeyCloak\Exceptions\UnknownScope as KeyCloakUnknownScope;
use App\Services\KeyCloak\Exceptions\UserDisabled;
use App\Services\Organization\Exceptions\UnknownOrganization as OrganizationUnknownOrganization;
use App\Services\Queue\Exceptions\ServiceNotFound as QueueServiceNotFound;
use App\Services\Settings\Exceptions\FailedToLoadEnv as SettingsFailedToLoadEnv;
use App\Services\Tokens\Exceptions\InvalidCredentials as TokensInvalidCredentials;
use Throwable;

class ErrorCodes {
    /**
     * @var array<class-string<\Throwable>,string|int>
     */
    protected static array $map = [
        QueueServiceNotFound::class                   => 'ERR01',
        FilesystemStorageFileCorrupted::class         => 'ERR03',
        FilesystemStorageFileDeleteFailed::class      => 'ERR04',
        FilesystemStorageFileSaveFailed::class        => 'ERR05',
        SettingsFailedToLoadEnv::class                => 'ERR06',
        ExportGraphQLQueryInvalid::class              => 'ERR07',
        KeyCloakAuthorizationFailed::class            => 'ERR08',
        KeyCloakStateMismatch::class                  => 'ERR09',
        KeyCloakInvalidIdentity::class                => 'ERR10',
        KeyCloakInvalidCredentials::class             => 'ERR11',
        KeyCloakInsufficientData::class               => 'ERR12',
        OrganizationUnknownOrganization::class        => 'ERR13',
        KeyCloakUnknownScope::class                   => 'ERR14',
        KeyCloakAnotherUserExists::class              => 'ERR15',
        TokensInvalidCredentials::class               => 'ERR16',
        ExportGraphQLQueryEmpty::class                => 'ERR17',
        DataLoaderDataLoaderDisabled::class           => 'ERR18',
        DataLoaderDataLoaderUnavailable::class        => 'ERR19',
        DataLoaderGraphQLRequestFailed::class         => 'ERR20',
        KeyCloakRequestFailed::class                  => 'ERR21',
        RealmGroupUnknown::class                      => 'ERR22',
        KeyCloakDisabled::class                       => 'ERR23',
        InvalidSettingClientUuid::class               => 'ERR24',
        KeyCloakRealmUserAlreadyExists::class         => 'ERR25',
        ResetPasswordSamePasswordException::class     => 'ERR27',
        SignUpByInviteInvalidToken::class             => 'ERR28',
        SignUpByInviteAlreadyUsed::class              => 'ERR31',
        UpdateMePasswordInvalidCurrentPassword::class => 'ERR33',
        ResetOrgUserPasswordInvalidUser::class        => 'ERR34',
        KeyCloakRealmUserNotFound::class              => 'ERR36',
        EnableOrgUserInvalidUser::class               => 'ERR37',
        DisableOrgUserInvalidUser::class              => 'ERR38',
        UserDisabled::class                           => 'ERR39',
    ];

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
