<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\Exceptions\Exceptions\FailedToSendMail as AppFailedToSendMail;
use App\GraphQL\Directives\Directives\Mutation\Exceptions\InvalidContext as GraphQLInvalidContext;
use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound as GraphQLObjectNotFound;
use App\GraphQL\Mutations\Auth\ResetPasswordSamePasswordException as GraphQLResetPasswordSamePasswordException;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvitationExpired as GraphQLSignUpByInviteInvitationExpired;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvitationNotFound as GraphQLSignUpByInviteInvitationNotFound;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvitationOrganizationNotFound
    as GraphQLSignUpByInviteInvitationOrganizationNotFound;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvitationOutdated as GraphQLSignUpByInviteInvitationOutdated;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvitationUsed as GraphQLSignUpByInviteInvitationUsed;
use App\GraphQL\Mutations\Auth\SignUpByInviteInvitationUserNotFound as GraphQLSignUpByInviteInvitationUserNotFound;
use App\GraphQL\Mutations\Auth\SignUpByInviteTokenInvalid as GraphQLSignUpByInviteTokenInvalid;
use App\GraphQL\Mutations\ImportOemsImportFailed as GraphQLImportOemsImportFailedAlias;
use App\GraphQL\Mutations\Me\UpdateMePasswordInvalidCurrentPassword as GraphQLUpdateMePasswordInvalidCurrentPassword;
use App\GraphQL\Mutations\Org\ResetOrgUserPasswordInvalidUser as GraphQLResetOrgUserPasswordInvalidUser;
use App\GraphQL\Mutations\Org\Role\DeleteImpossibleAssignedToUsers as GraphQLDeleteImpossibleAssignedToUsers;
use App\GraphQL\Mutations\Organization\User\InviteImpossibleKeycloakUserDisabled
    as GraphQLInviteImpossibleKeycloakUserDisabled;
use App\Http\Controllers\Export\GraphQLQueryInvalid as HttpExportGraphQLQueryInvalid;
use App\Http\Controllers\Export\HeadersUnknownFunction as HttpExportHeadersUnknownFunction;
use App\Services\DataLoader\Client\Exceptions\DataLoaderDisabled as DataLoaderDataLoaderDisabled;
use App\Services\DataLoader\Client\Exceptions\DataLoaderUnavailable as DataLoaderDataLoaderUnavailable;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed as DataLoaderGraphQLRequestFailed;
use App\Services\Filesystem\Exceptions\StorageFileCorrupted as FilesystemStorageFileCorrupted;
use App\Services\Filesystem\Exceptions\StorageFileDeleteFailed as FilesystemStorageFileDeleteFailed;
use App\Services\Filesystem\Exceptions\StorageFileSaveFailed as FilesystemStorageFileSaveFailed;
use App\Services\Keycloak\Client\Exceptions\InvalidSettingClientUuid as KeycloakInvalidSettingClientUuid;
use App\Services\Keycloak\Client\Exceptions\KeycloakDisabled as KeycloakKeycloakDisabled;
use App\Services\Keycloak\Client\Exceptions\KeycloakUnavailable as KeycloakKeycloakUnavailable;
use App\Services\Keycloak\Client\Exceptions\RealmGroupUnknown as KeycloakRealmGroupUnknown;
use App\Services\Keycloak\Client\Exceptions\RealmRoleAlreadyExists as KeycloakRealmRoleAlreadyExistsAlias;
use App\Services\Keycloak\Client\Exceptions\RealmUserAlreadyExists as KeycloakRealmUserAlreadyExists;
use App\Services\Keycloak\Client\Exceptions\RealmUserNotFound as KeycloakRealmUserNotFound;
use App\Services\Keycloak\Client\Exceptions\RequestFailed as KeycloakRequestFailed;
use App\Services\Keycloak\Client\Exceptions\ServerError as KeycloakServerError;
use App\Services\Keycloak\Exceptions\Auth\AnotherUserExists as KeycloakAnotherUserExists;
use App\Services\Keycloak\Exceptions\Auth\AuthorizationFailed as KeycloakAuthorizationFailed;
use App\Services\Keycloak\Exceptions\Auth\InvalidCredentials as KeycloakInvalidCredentials;
use App\Services\Keycloak\Exceptions\Auth\InvalidIdentity as KeycloakInvalidIdentity;
use App\Services\Keycloak\Exceptions\Auth\StateMismatch as KeycloakStateMismatch;
use App\Services\Keycloak\Exceptions\Auth\UnknownScope as KeycloakUnknownScope;
use App\Services\Keycloak\Exceptions\Auth\UserDisabled as KeycloakUserDisabled;
use App\Services\Keycloak\Exceptions\Auth\UserInsufficientData as KeycloakUserInsufficientData;
use App\Services\Organization\Exceptions\UnknownOrganization as OrganizationUnknownOrganization;
use App\Services\Queue\Exceptions\ServiceNotFound as QueueServiceNotFound;
use App\Services\Settings\Exceptions\FailedToLoadSettings as SettingsFailedToLoadSettings;
use App\Services\Settings\Exceptions\FailedToSaveSettings as SettingsFailedToSaveSettings;
use App\Services\Tokens\Exceptions\InvalidCredentials as TokensInvalidCredentials;
use Throwable;

class ErrorCodes {
    /**
     * @var array<class-string<Throwable>,string|int>
     */
    protected static array $map = [
        // App
        AppFailedToSendMail::class                                 => 'App001',

        // Http
        HttpExportGraphQLQueryInvalid::class                       => 'Http001',
        HttpExportHeadersUnknownFunction::class                    => 'Http002',

        // GraphQL
        GraphQLResetPasswordSamePasswordException::class           => 'GraphQL001',
        GraphQLSignUpByInviteTokenInvalid::class                   => 'GraphQL002',
        GraphQLSignUpByInviteInvitationUsed::class                 => 'GraphQL003',
        GraphQLUpdateMePasswordInvalidCurrentPassword::class       => 'GraphQL004',
        GraphQLResetOrgUserPasswordInvalidUser::class              => 'GraphQL005',
        GraphQLSignUpByInviteInvitationExpired::class              => 'GraphQL006',
        GraphQLSignUpByInviteInvitationNotFound::class             => 'GraphQL007',
        GraphQLDeleteImpossibleAssignedToUsers::class              => 'GraphQL008',
        GraphQLInvalidContext::class                               => 'GraphQL009',
        GraphQLObjectNotFound::class                               => 'GraphQL010',
        GraphQLSignUpByInviteInvitationOutdated::class             => 'GraphQL011',
        GraphQLSignUpByInviteInvitationUserNotFound::class         => 'GraphQL012',
        GraphQLSignUpByInviteInvitationOrganizationNotFound::class => 'GraphQL013',
        GraphQLInviteImpossibleKeycloakUserDisabled::class         => 'GraphQL014',
        GraphQLImportOemsImportFailedAlias::class                  => 'GraphQL015',

        // Queue
        QueueServiceNotFound::class                                => 'Queue001',

        // Filesystem
        FilesystemStorageFileCorrupted::class                      => 'Filesystem001',
        FilesystemStorageFileDeleteFailed::class                   => 'Filesystem002',
        FilesystemStorageFileSaveFailed::class                     => 'Filesystem003',

        // Settings
        SettingsFailedToLoadSettings::class                        => 'Settings001',
        SettingsFailedToSaveSettings::class                        => 'Settings002',

        // Keycloak
        KeycloakAuthorizationFailed::class                         => 'Keycloak001',
        KeycloakStateMismatch::class                               => 'Keycloak002',
        KeycloakInvalidIdentity::class                             => 'Keycloak003',
        KeycloakInvalidCredentials::class                          => 'Keycloak004',
        KeycloakUserInsufficientData::class                        => 'Keycloak005',
        KeycloakUnknownScope::class                                => 'Keycloak006',
        KeycloakAnotherUserExists::class                           => 'Keycloak007',
        KeycloakRequestFailed::class                               => 'Keycloak008',
        KeycloakRealmGroupUnknown::class                           => 'Keycloak009',
        KeycloakKeycloakDisabled::class                            => 'Keycloak010',
        KeycloakInvalidSettingClientUuid::class                    => 'Keycloak011',
        KeycloakRealmUserAlreadyExists::class                      => 'Keycloak012',
        KeycloakRealmUserNotFound::class                           => 'Keycloak013',
        KeycloakUserDisabled::class                                => 'Keycloak014',
        KeycloakKeycloakUnavailable::class                         => 'Keycloak015',
        KeycloakServerError::class                                 => 'Keycloak016',
        KeycloakRealmRoleAlreadyExistsAlias::class                 => 'Keycloak017',

        // Organization
        OrganizationUnknownOrganization::class                     => 'Organization001',

        // Tokens
        TokensInvalidCredentials::class                            => 'Tokens001',

        // DataLoader
        DataLoaderDataLoaderDisabled::class                        => 'DataLoader001',
        DataLoaderDataLoaderUnavailable::class                     => 'DataLoader002',
        DataLoaderGraphQLRequestFailed::class                      => 'DataLoader003',
    ];

    /**
     * @return array<class-string<Throwable>,string|int>
     */
    public static function getMap(): array {
        return self::$map;
    }

    public static function getCode(Throwable $throwable): string|int {
        return self::$map[$throwable::class] ?? $throwable->getCode();
    }
}
