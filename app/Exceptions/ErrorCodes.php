<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\Auth\ResetPasswordSamePasswordException as GraphQLResetPasswordSamePasswordException;
use App\GraphQL\Mutations\Auth\SignUpByInviteAlreadyUsed as GraphQLSignUpByInviteAlreadyUsed;
use App\GraphQL\Mutations\Auth\SignUpByInviteExpired as GraphQLSignUpByInviteExpired;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvalidToken as GraphQLSignUpByInviteInvalidToken;
use App\GraphQL\Mutations\Auth\SignUpByInviteNotFound as GraphQLSignUpByInviteNotFound;
use App\GraphQL\Mutations\Me\UpdateMePasswordInvalidCurrentPassword as GraphQLUpdateMePasswordInvalidCurrentPassword;
use App\GraphQL\Mutations\Org\DisableOrgUserInvalidUser as GraphQLDisableOrgUserInvalidUser;
use App\GraphQL\Mutations\Org\EnableOrgUserInvalidUser as GraphQLEnableOrgUserInvalidUser;
use App\GraphQL\Mutations\Org\ResetOrgUserPasswordInvalidUser as GraphQLResetOrgUserPasswordInvalidUser;
use App\Http\Controllers\ExportGraphQLQueryEmpty as HttpExportGraphQLQueryEmpty;
use App\Http\Controllers\ExportGraphQLQueryInvalid as HttpExportGraphQLQueryInvalid;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled as DataLoaderDataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable as DataLoaderDataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed as DataLoaderGraphQLRequestFailed;
use App\Services\Filesystem\Exceptions\StorageFileCorrupted as FilesystemStorageFileCorrupted;
use App\Services\Filesystem\Exceptions\StorageFileDeleteFailed as FilesystemStorageFileDeleteFailed;
use App\Services\Filesystem\Exceptions\StorageFileSaveFailed as FilesystemStorageFileSaveFailed;
use App\Services\KeyCloak\Client\Exceptions\InvalidSettingClientUuid as KeyCloakInvalidSettingClientUuid;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled as KeyCloakKeyCloakDisabled;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakUnavailable as KeyCloakKeyCloakUnavailable;
use App\Services\KeyCloak\Client\Exceptions\RealmGroupUnknown as KeyCloakRealmGroupUnknown;
use App\Services\KeyCloak\Client\Exceptions\RealmUserAlreadyExists as KeyCloakRealmUserAlreadyExists;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound as KeyCloakRealmUserNotFound;
use App\Services\KeyCloak\Client\Exceptions\RequestFailed as KeyCloakRequestFailed;
use App\Services\KeyCloak\Client\Exceptions\ServerError as KeyCloakServerError;
use App\Services\KeyCloak\Exceptions\AnotherUserExists as KeyCloakAnotherUserExists;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed as KeyCloakAuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InvalidCredentials as KeyCloakInvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity as KeyCloakInvalidIdentity;
use App\Services\KeyCloak\Exceptions\StateMismatch as KeyCloakStateMismatch;
use App\Services\KeyCloak\Exceptions\UnknownScope as KeyCloakUnknownScope;
use App\Services\KeyCloak\Exceptions\UserDisabled as KeyCloakUserDisabled;
use App\Services\KeyCloak\Exceptions\UserInsufficientData as KeyCloakUserInsufficientData;
use App\Services\Organization\Exceptions\UnknownOrganization as OrganizationUnknownOrganization;
use App\Services\Queue\Exceptions\ServiceNotFound as QueueServiceNotFound;
use App\Services\Settings\Exceptions\FailedToLoadConfig as SettingsFailedToLoadConfig;
use App\Services\Settings\Exceptions\FailedToLoadEnv as SettingsFailedToLoadEnv;
use App\Services\Tokens\Exceptions\InvalidCredentials as TokensInvalidCredentials;
use Throwable;

class ErrorCodes {
    /**
     * @var array<class-string<\Throwable>,string|int>
     */
    protected static array $map = [
        // Http
        HttpExportGraphQLQueryInvalid::class                 => 'Http001',
        HttpExportGraphQLQueryEmpty::class                   => 'Http002',

        // GraphQL
        GraphQLResetPasswordSamePasswordException::class     => 'GraphQL001',
        GraphQLSignUpByInviteInvalidToken::class             => 'GraphQL002',
        GraphQLSignUpByInviteAlreadyUsed::class              => 'GraphQL003',
        GraphQLUpdateMePasswordInvalidCurrentPassword::class => 'GraphQL004',
        GraphQLResetOrgUserPasswordInvalidUser::class        => 'GraphQL005',
        GraphQLEnableOrgUserInvalidUser::class               => 'GraphQL006',
        GraphQLDisableOrgUserInvalidUser::class              => 'GraphQL007',
        GraphQLSignUpByInviteExpired::class                  => 'GraphQL008',
        GraphQLSignUpByInviteNotFound::class                 => 'GraphQL009',

        // Queue
        QueueServiceNotFound::class                          => 'Queue001',

        // Filesystem
        FilesystemStorageFileCorrupted::class                => 'Filesystem001',
        FilesystemStorageFileDeleteFailed::class             => 'Filesystem002',
        FilesystemStorageFileSaveFailed::class               => 'Filesystem003',

        // Settings
        SettingsFailedToLoadEnv::class                       => 'Settings001',
        SettingsFailedToLoadConfig::class                    => 'Settings002',

        // KeyCloak
        KeyCloakAuthorizationFailed::class                   => 'KeyCloak001',
        KeyCloakStateMismatch::class                         => 'KeyCloak002',
        KeyCloakInvalidIdentity::class                       => 'KeyCloak003',
        KeyCloakInvalidCredentials::class                    => 'KeyCloak004',
        KeyCloakUserInsufficientData::class                  => 'KeyCloak005',
        KeyCloakUnknownScope::class                          => 'KeyCloak006',
        KeyCloakAnotherUserExists::class                     => 'KeyCloak007',
        KeyCloakRequestFailed::class                         => 'KeyCloak008',
        KeyCloakRealmGroupUnknown::class                     => 'KeyCloak009',
        KeyCloakKeyCloakDisabled::class                      => 'KeyCloak010',
        KeyCloakInvalidSettingClientUuid::class              => 'KeyCloak011',
        KeyCloakRealmUserAlreadyExists::class                => 'KeyCloak012',
        KeyCloakRealmUserNotFound::class                     => 'KeyCloak013',
        KeyCloakUserDisabled::class                          => 'KeyCloak014',
        KeyCloakKeyCloakUnavailable::class                   => 'KeyCloak015',
        KeyCloakServerError::class                           => 'KeyCloak016',

        // Organization
        OrganizationUnknownOrganization::class               => 'Organization001',

        // Tokens
        TokensInvalidCredentials::class                      => 'Tokens001',

        // DataLoader
        DataLoaderDataLoaderDisabled::class                  => 'DataLoader001',
        DataLoaderDataLoaderUnavailable::class               => 'DataLoader002',
        DataLoaderGraphQLRequestFailed::class                => 'DataLoader003',
    ];

    /**
     * @return array<class-string<\Throwable>,string|int>
     */
    public static function getMap(): array {
        return self::$map;
    }

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
