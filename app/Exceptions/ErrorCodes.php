<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\GraphQL\Mutations\Auth\ResetPasswordSamePasswordException;
use App\GraphQL\Mutations\Auth\SignUpByInviteAlreadyUsed;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvalidToken;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvalidUser;
use App\GraphQL\Mutations\Auth\SignUpByInviteUnInvitedUser;
use App\GraphQL\Mutations\Org\InviteOrgUserAlreadyUsedInvitation;
use App\GraphQL\Mutations\Org\InviteOrgUserInvalidRole;
use App\GraphQL\Mutations\Org\ResetOrgUserPasswordInvalidUser;
use App\GraphQL\Mutations\UpdateMeEmailUserAlreadyExists;
use App\GraphQL\Mutations\UpdateMePasswordInvalidCurrentPassword;
use App\Http\Controllers\ExportGraphQLQueryEmpty;
use App\Http\Controllers\ExportGraphQLQueryInvalid;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled as DataLoaderDataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable as DataLoaderDataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed as DataLoaderGraphQLRequestFailed;
use App\Services\Filesystem\StorageFileCorrupted as FilesystemStorageFileCorrupted;
use App\Services\Filesystem\StorageFileDeleteFailed as FilesystemStorageFileDeleteFailed;
use App\Services\Filesystem\StorageFileSaveFailed as FilesystemStorageFileSaveFailed;
use App\Services\KeyCloak\Client\Exceptions\EndpointException as KeyCloakEndpointException;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakClient;
use App\Services\KeyCloak\Client\Exceptions\InvalidKeyCloakGroup;
use App\Services\KeyCloak\Client\Exceptions\KeyCloakDisabled;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists as KeyCloakUserAlreadyExists;
use App\Services\KeyCloak\Exceptions\AnotherUserExists as KeyCloakAnotherUserExists;
use App\Services\KeyCloak\Exceptions\AuthorizationFailed as KeyCloakAuthorizationFailed;
use App\Services\KeyCloak\Exceptions\InsufficientData as KeyCloakInsufficientData;
use App\Services\KeyCloak\Exceptions\InvalidCredentials as KeyCloakInvalidCredentials;
use App\Services\KeyCloak\Exceptions\InvalidIdentity as KeyCloakInvalidIdentity;
use App\Services\KeyCloak\Exceptions\StateMismatch as KeyCloakStateMismatch;
use App\Services\KeyCloak\Exceptions\UnknownScope as KeyCloakUnknownScope;
use App\Services\Organization\Exceptions\UnknownOrganization as OrganizationUnknownOrganization;
use App\Services\Queue\Exceptions\ServiceNotFound as QueueServiceNotFound;
use App\Services\Settings\Exceptions\SettingsFailedToLoadEnv as SettingsSettingsFailedToLoadEnv;
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
        SettingsSettingsFailedToLoadEnv::class        => 'ERR06',
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
        KeyCloakEndpointException::class              => 'ERR21',
        InvalidKeyCloakGroup::class                   => 'ERR22',
        KeyCloakDisabled::class                       => 'ERR23',
        InvalidKeyCloakClient::class                  => 'ERR24',
        KeyCloakUserAlreadyExists::class              => 'ERR25',
        InviteOrgUserInvalidRole::class               => 'ERR26',
        ResetPasswordSamePasswordException::class     => 'ERR27',
        SignUpByInviteInvalidToken::class             => 'ERR28',
        SignUpByInviteInvalidUser::class              => 'ERR29',
        SignUpByInviteUnInvitedUser::class            => 'ERR30',
        SignUpByInviteAlreadyUsed::class              => 'ERR31',
        InviteOrgUserAlreadyUsedInvitation::class     => 'ERR32',
        UpdateMePasswordInvalidCurrentPassword::class => 'ERR33',
        ResetOrgUserPasswordInvalidUser::class        => 'ERR34',
        UpdateMeEmailUserAlreadyExists::class         => 'ERR35',
    ];

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
