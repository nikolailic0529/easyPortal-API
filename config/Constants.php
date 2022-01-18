<?php declare(strict_types = 1);

namespace Config;

use App\Queues;
use App\Services\DataLoader\Jobs\AssetsImporterCronJob;
use App\Services\DataLoader\Jobs\AssetsUpdaterCronJob;
use App\Services\DataLoader\Jobs\CustomersImporterCronJob;
use App\Services\DataLoader\Jobs\CustomersUpdaterCronJob;
use App\Services\DataLoader\Jobs\DistributorsImporterCronJob;
use App\Services\DataLoader\Jobs\DistributorsUpdaterCronJob;
use App\Services\DataLoader\Jobs\DocumentsImporterCronJob;
use App\Services\DataLoader\Jobs\DocumentsUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use App\Services\KeyCloak\Jobs\SyncPermissionsCronJob;
use App\Services\KeyCloak\Jobs\SyncUsersCronJob;
use App\Services\Logger\Logger;
use App\Services\Maintenance\Jobs\CompleteCronJob as MaintenanceCompleteCronJob;
use App\Services\Maintenance\Jobs\NotifyCronJob as MaintenanceNotifyCronJob;
use App\Services\Maintenance\Jobs\StartCronJob as MaintenanceStartCronJob;
use App\Services\Queue\Jobs\SnapshotCronJob as QueueSnapshotCronJob;
use App\Services\Search\Jobs\AssetsUpdaterCronJob as SearchAssetsUpdaterCronJob;
use App\Services\Search\Jobs\CustomersUpdaterCronJob as SearchCustomersUpdaterCronJob;
use App\Services\Search\Jobs\DocumentsUpdaterCronJob as SearchDocumentsUpdaterCronJob;
use App\Services\Settings\Attributes\Group;
use App\Services\Settings\Attributes\Internal;
use App\Services\Settings\Attributes\Job;
use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Secret;
use App\Services\Settings\Attributes\Service;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Attributes\Type;
use App\Services\Settings\Jobs\ConfigUpdate;
use App\Services\Settings\Types\BooleanType;
use App\Services\Settings\Types\CronExpression;
use App\Services\Settings\Types\DateTime;
use App\Services\Settings\Types\DocumentType;
use App\Services\Settings\Types\Duration;
use App\Services\Settings\Types\Email;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\LocationType;
use App\Services\Settings\Types\LogLevel;
use App\Services\Settings\Types\Organization;
use App\Services\Settings\Types\StringType;
use App\Services\Settings\Types\Text;
use App\Services\Settings\Types\Url;
use Psr\Log\LogLevel as PsrLogLevel;

/**
 * A list of application settings.
 *
 * Settings priorities:
 * - .env (used only if the application configuration is NOT cached)
 * - this list
 * - other configuration files
 */
interface Constants {
    // <editor-fold desc="MAIL">
    // =========================================================================
    /**
     * Mailer.
     */
    #[Setting]
    #[Group('mail')]
    public const MAIL_MAILER = 'smtp';

    /**
     * Host.
     */
    #[Setting]
    #[Group('mail')]
    public const MAIL_HOST = 'smtp-relay.sendinblue.com';

    /**
     * Port.
     */
    #[Setting]
    #[Group('mail')]
    public const MAIL_PORT = 587;

    /**
     * Username.
     */
    #[Setting]
    #[Group('mail')]
    public const MAIL_USERNAME = '';

    /**
     * Password.
     */
    #[Setting]
    #[Secret]
    #[Group('mail')]
    public const MAIL_PASSWORD = '';

    /**
     * Encryption.
     */
    #[Setting]
    #[Group('mail')]
    #[Type(StringType::class)]
    public const MAIL_ENCRYPTION = null;

    /**
     * From address.
     */
    #[Setting]
    #[Group('mail')]
    public const MAIL_FROM_ADDRESS = 'info@itassethub.test';

    /**
     * From name.
     */
    #[Setting]
    #[Group('mail')]
    public const MAIL_FROM_NAME = 'IT Asset Hub';
    // </editor-fold>

    // <editor-fold desc="CLOCKWORK">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting]
    #[Group('clockwork')]
    public const CLOCKWORK_ENABLE = false;

    /**
     * Storage.
     */
    #[Setting]
    #[Internal]
    #[Group('clockwork')]
    public const CLOCKWORK_STORAGE = 'sql';

    /**
     * Database.
     */
    #[Setting]
    #[Internal]
    #[Group('clockwork')]
    public const CLOCKWORK_STORAGE_SQL_DATABASE = Logger::CONNECTION;

    /**
     * Maximum lifetime of collected metadata in minutes, older requests will automatically be deleted.
     */
    #[Setting]
    #[Group('clockwork')]
    public const CLOCKWORK_STORAGE_EXPIRATION = 7 * 24 * 60;
    // </editor-fold>

    // <editor-fold desc="SENTRY">
    // =========================================================================
    /**
     * DSN
     */
    #[Setting]
    #[Group('sentry')]
    #[Type(StringType::class)]
    public const SENTRY_LARAVEL_DSN = null;
    // </editor-fold>

    // <editor-fold desc="EP">
    // =========================================================================
    /**
     * Max size of branding images/icons (branding_favicon, branding_logo) in KB.
     */
    #[Setting('ep.image.max_size')]
    #[PublicName('epImageMaxSize')]
    #[Group('ep')]
    public const EP_IMAGE_MAX_SIZE = 2048;

    /**
     * Accepted image formats.
     */
    #[Setting('ep.image.formats')]
    #[PublicName('epImageFormats')]
    #[Group('ep')]
    #[Type(StringType::class)]
    public const EP_IMAGE_FORMATS = ['jpg', 'jpeg', 'png'];

    /**
     * Type IDs related to contracts.
     *
     * If changed Assets must be recalculated.
     */
    #[Setting('ep.contract_types')]
    #[Group('ep')]
    #[Type(DocumentType::class)]
    public const EP_CONTRACT_TYPES = [];

    /**
     * Types IDs related to quotes. Optional, if empty will use IDs which are
     * not in {@link \Config\Constants::EP_CONTRACT_TYPES}.
     *
     * If changed Assets must be recalculated.
     */
    #[Setting('ep.quote_types')]
    #[Group('ep')]
    #[Type(DocumentType::class)]
    public const EP_QUOTE_TYPES = [];

    /**
     * Type ID related to headquarter.
     */
    #[Setting('ep.headquarter_type')]
    #[Group('ep')]
    #[Type(LocationType::class)]
    public const EP_HEADQUARTER_TYPE = '';

    /**
     * Root organization ID.
     */
    #[Setting('ep.root_organization')]
    #[Group('ep')]
    #[Type(Organization::class)]
    public const EP_ROOT_ORGANIZATION = '40765bbb-4736-4d2f-8964-1c3fd4e59aac';

    /**
     * Max size of uploaded files in KB.
     */
    #[Setting('ep.file.max_size')]
    #[PublicName('epFileMaxSize')]
    #[Group('ep')]
    public const EP_FILE_MAX_SIZE = 2048;

    /**
     * Accepted file/document formats.
     */
    #[Setting('ep.file.formats')]
    #[PublicName('epFileFormats')]
    #[Group('ep')]
    #[Type(StringType::class)]
    public const EP_FILE_FORMATS = ['jpg', 'jpeg', 'png', 'csv', 'xlsx', 'pdf', 'docx', 'doc'];

    /**
     * Tesedi Portal Email Address.
     */
    #[Setting('ep.email_address')]
    #[PublicName('epEmailAddress')]
    #[Group('ep')]
    #[Type(Email::class)]
    public const EP_EMAIL_ADDRESS = 'info@itassethub.test';

    /**
     * Invitation expiration duration.
     */
    #[Setting('ep.invite_expire')]
    #[Group('ep')]
    #[Type(Duration::class)]
    public const EP_INVITE_EXPIRE = 'PT24H';

    /**
     * Pagination: Default value for `limit`.
     */
    #[Setting('ep.pagination.limit.default')]
    #[PublicName('epPaginationLimitDefault')]
    #[Group('ep')]
    #[Type(IntType::class)]
    public const EP_PAGINATION_LIMIT_DEFAULT = 25;

    /**
     * Pagination: Max allowed value of `limit`.
     */
    #[Setting('ep.pagination.limit.max')]
    #[PublicName('epPaginationLimitMax')]
    #[Group('ep')]
    #[Type(IntType::class)]
    public const EP_PAGINATION_LIMIT_MAX = 100;
    // </editor-fold>

    // <editor-fold desc="EP_LOG">
    // =========================================================================
    /**
     * Minimum severity that should be logged.
     */
    #[Setting]
    #[Group('log')]
    #[Type(LogLevel::class)]
    public const LOG_LEVEL = PsrLogLevel::DEBUG;

    /**
     * Send errors to Sentry?
     */
    #[Setting]
    #[Group('log')]
    public const EP_LOG_SENTRY_ENABLED = false;

    /**
     * Minimum severity that should be logged via Sentry.
     */
    #[Setting]
    #[Group('log')]
    #[Type(LogLevel::class)]
    public const EP_LOG_SENTRY_LEVEL = self::LOG_LEVEL;

    /**
     * Send errors to emails?
     */
    #[Setting]
    #[Group('log')]
    public const EP_LOG_EMAIL_ENABLED = false;

    /**
     * Minimum severity that should be logged via emails.
     */
    #[Setting]
    #[Group('log')]
    #[Type(LogLevel::class)]
    public const EP_LOG_EMAIL_LEVEL = PsrLogLevel::ERROR;

    /**
     * Email addresses that will receive errors.
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[Group('log')]
    #[Type(Email::class)]
    public const EP_LOG_EMAIL_RECIPIENTS = ['chief.wraith+notice@gmail.com'];
    // </editor-fold>

    // <editor-fold desc="EP_CACHE">
    // =========================================================================
    /**
     * Services data TTL (jobs progress, etc).
     */
    #[Setting('ep.cache.service.ttl')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_SERVICE_TTL = 'P6M';

    /**
     * GraphQL TTL.
     */
    #[Setting('ep.cache.graphql.ttl')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_TTL = 'P2W';

    /**
     * GraphQL lock timeout (seconds).
     *
     * The `@cached` directive tries to reduce server load, so it executes the
     * root queries only in one process/request, all other processes/requests
     * just wait.
     *
     * These settings determine lock timeout (how long the lock may exist) and a
     * wait timeout (how long another process/request will wait before starting
     * to execute the code by self).
     */
    #[Setting('ep.cache.graphql.lock')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_LOCK = 30;

    /**
     * GraphQL wait timeout (seconds).
     *
     * Please see {@link \Config\Constants::EP_CACHE_GRAPHQL_LOCK}
     */
    #[Setting('ep.cache.graphql.wait')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_WAIT = 35;

    /**
     * GraphQL threshold (seconds with fraction part).
     *
     * Nested queries faster than this value will not be cached. The `0`
     * disable threshold so all queries will be cached.
     */
    #[Setting('ep.cache.graphql.threshold')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_THRESHOLD = 2.0;
    // </editor-fold>

    // <editor-fold desc="EP_AUTH">
    // =========================================================================
    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[Group('auth')]
    #[Type(Email::class)]
    public const EP_AUTH_LOG_EMAIL_RECIPIENTS = [];
    // </editor-fold>

    // <editor-fold desc="EP_CLIENT">
    // =========================================================================
    /**
     * The URI (can be relative) where user should be redirected to complete
     * Password Reset.
     *
     * Replacements:
     * * `{token}` - token
     * * `{email}` - email
     */
    #[Setting('ep.client.password_reset_uri')]
    #[Group('client')]
    #[Type(StringType::class)]
    public const EP_CLIENT_PASSWORD_RESET_URI = 'auth/reset-password/{token}?email={email}';

    /**
     * The URI (can be relative) where user should be redirected to add his password
     * Access EP after invitation.
     *
     * Replacements:
     * * `{token}` - token
     */
    #[Setting('ep.client.signup_invite_uri')]
    #[Group('client')]
    #[Type(StringType::class)]
    public const EP_CLIENT_SIGNUP_INVITE_URI = 'auth/signup/{token}';

    /**
     * The URI (can be relative) where user should be redirected to access portal.
     * Access EP after invitation.
     *
     * Replacements:
     * * `{organization}` - organization
     */
    #[Setting('ep.client.signin_invite_uri')]
    #[Group('client')]
    #[Type(StringType::class)]
    public const EP_CLIENT_SIGNIN_INVITE_URI = 'auth/organizations/{organization}';

    /**
     * The URI (can be relative) where user should be redirected to complete
     * Sign Up by invitation.
     *
     * Replacements:
     * * `{token}` - token (required)
     */
    #[Setting('ep.client.invite_uri')]
    #[Group('client')]
    #[Type(StringType::class)]
    public const EP_CLIENT_INVITE_URI = 'auth/signup/{token}';
    //</editor-fold>

    // <editor-fold desc="EP_KEYCLOAK">
    // =========================================================================
    /**
     * Server URL.
     */
    #[Setting('ep.keycloak.url')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_URL = null;

    /**
     * Realm.
     */
    #[Setting('ep.keycloak.realm')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_REALM = null;

    /**
     * Client Id.
     */
    #[Setting('ep.keycloak.client_id')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_ID = null;

    /**
     * Keycloak client uuid.
     */
    #[Setting('ep.keycloak.client_uuid')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_UUID = null;

    /**
     * Client Secret.
     */
    #[Setting('ep.keycloak.client_secret')]
    #[Secret]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_SECRET = null;

    /**
     * The URI (can be relative) where user should be redirected after Sign In.
     *
     * Replacements:
     * * `{organization}` - current organization id
     */
    #[Setting('ep.keycloak.redirects.signin_uri')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_REDIRECTS_SIGNIN_URI = 'auth/organizations/{organization}';

    /**
     * The URI (can be relative) where user should be redirected after Sign Out.
     *
     * Replacements:
     * * `{organization}` - current organization id
     */
    #[Setting('ep.keycloak.redirects.signout_uri')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_REDIRECTS_SIGNOUT_URI = 'auth/organizations/{organization}';

    /**
     * Encryption Algorithm.
     */
    #[Setting('ep.keycloak.encryption.algorithm')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_ENCRYPTION_ALGORITHM = 'RS256';

    /**
     * Encryption Public Key.
     */
    #[Setting('ep.keycloak.encryption.public_key')]
    #[Group('keycloak')]
    #[Type(Text::class)]
    public const EP_KEYCLOAK_ENCRYPTION_PUBLIC_KEY = '';

    /**
     * Leeway for JWT validation.
     */
    #[Setting('ep.keycloak.leeway')]
    #[Group('keycloak')]
    #[Type(Duration::class)]
    public const EP_KEYCLOAK_LEEWAY = null;

    /**
     * Default timeout for http requests (in seconds).
     */
    #[Setting('ep.keycloak.timeout')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_TIMEOUT = 5 * 60;

    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[Group('keycloak')]
    #[Type(Email::class)]
    public const EP_KEYCLOAK_LOG_EMAIL_RECIPIENTS = [];

    /**
     * OrgAdmin Role UUID. You should sync KeyCloak permissions via command or
     * job after the setting changed.
     */
    #[Setting('ep.keycloak.org_admin_group')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_ORG_ADMIN_GROUP = null;

    // <editor-fold desc="EP_KEYCLOAK_SYNC_PERMISSIONS">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(SyncPermissionsCronJob::class, 'enabled')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_SYNC_PERMISSIONS_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(SyncPermissionsCronJob::class, 'cron')]
    #[Group('keycloak')]
    #[Type(CronExpression::class)]
    public const EP_KEYCLOAK_SYNC_PERMISSIONS_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(SyncPermissionsCronJob::class, 'queue')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_SYNC_PERMISSIONS_QUEUE = Queues::KEYCLOAK;
    // </editor-fold>

    // <editor-fold desc="EP_KEYCLOAK_SYNC_USERS">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(SyncUsersCronJob::class, 'enabled')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_SYNC_USERS_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(SyncUsersCronJob::class, 'cron')]
    #[Group('keycloak')]
    #[Type(CronExpression::class)]
    public const EP_KEYCLOAK_SYNC_USERS_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(SyncUsersCronJob::class, 'queue')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_SYNC_USERS_QUEUE = Queues::KEYCLOAK;
    // </editor-fold>

    // </editor-fold>

    // <editor-fold desc="EP_SETTINGS">
    // =========================================================================
    // <editor-fold desc="EP_SETTINGS_CONFIG_UPDATE">
    // -------------------------------------------------------------------------
    /**
     * Queue name.
     */
    #[Job(ConfigUpdate::class, 'queue')]
    #[Group('ep')]
    public const EP_SETTINGS_CONFIG_UPDATE_QUEUE = Queues::DEFAULT;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_LOGGER">
    // =========================================================================
    /**
     * Logger enabled?
     */
    #[Setting('ep.logger.enabled')]
    #[Group('logger')]
    public const EP_LOGGER_ENABLED = false;

    /**
     * How often long-running actions should be dumped?
     */
    #[Setting('ep.logger.dump')]
    #[Group('logger')]
    #[Type(Duration::class)]
    public const EP_LOGGER_DUMP = 'PT5M';

    /**
     * Log models changes?
     */
    #[Setting('ep.logger.eloquent.models')]
    #[Group('logger')]
    public const EP_LOGGER_ELOQUENT_MODELS = false;

    /**
     * Log DataLoader queries?
     */
    #[Setting('ep.logger.data_loader.queries')]
    #[Group('logger')]
    public const EP_LOGGER_DATA_LOADER_QUERIES = false;

    /**
     * Log DataLoader mutations?
     */
    #[Setting('ep.logger.data_loader.mutations')]
    #[Group('logger')]
    public const EP_LOGGER_DATA_LOADER_MUTATIONS = false;
    //</editor-fold>

    // <editor-fold desc="EP_DATA_LOADER">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting('ep.data_loader.enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ENABLED = true;

    /**
     * URL.
     */
    #[Setting('ep.data_loader.url')]
    #[Group('data_loader')]
    #[Type(Url::class)]
    public const EP_DATA_LOADER_URL = '';

    /**
     * Client ID.
     */
    #[Setting('ep.data_loader.client_id')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CLIENT_ID = '';

    /**
     * Client Secret.
     */
    #[Setting('ep.data_loader.client_secret')]
    #[Secret]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CLIENT_SECRET = '';

    /**
     * Default chunk size.
     */
    #[Setting('ep.data_loader.chunk')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CHUNK = 250;

    /**
     * Default timeout for http requests (in seconds).
     */
    #[Setting('ep.data_loader.timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_TIMEOUT = 5 * 60;

    /**
     * GraphQL Endpoint (optional, if empty {@link \Config\Constants::EP_DATA_LOADER_URL} will be used).
     */
    #[Setting('ep.data_loader.endpoint')]
    #[Group('data_loader')]
    #[Type(Url::class)]
    public const EP_DATA_LOADER_ENDPOINT = null;

    /**
     * GraphQL queries that take more than `X` seconds will be logged (set to `0` to disable)
     */
    #[Setting('ep.data_loader.slowlog')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_SLOWLOG = 0;

    /**
     * Dump queries into specified directory.
     */
    #[Setting('ep.data_loader.dump')]
    #[Group('data_loader')]
    #[Type(StringType::class)]
    #[Internal]
    public const EP_DATA_LOADER_DUMP = null;

    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[Group('data_loader')]
    #[Type(Email::class)]
    public const EP_DATA_LOADER_LOG_EMAIL_RECIPIENTS = [];

    // <editor-fold desc="EP_DATA_LOADER_RESELLERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersImporterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(ResellersImporterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersImporterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(ResellersImporterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_TIMEOUT = 1 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(ResellersImporterCronJob::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_TRIES = 1;

    /**
     * Chunk size.
     */
    #[Service(ResellersImporterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Update existing objects?
     */
    #[Service(ResellersImporterCronJob::class, 'settings.update')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_UPDATE = true;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_RESELLERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersUpdaterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(ResellersUpdaterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(ResellersUpdaterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_TIMEOUT = self::EP_DATA_LOADER_RESELLERS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(ResellersUpdaterCronJob::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_TRIES = self::EP_DATA_LOADER_RESELLERS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(ResellersUpdaterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(ResellersUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_CUSTOMERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(CustomersImporterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(CustomersImporterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(CustomersImporterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(CustomersImporterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_TIMEOUT = 6 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(CustomersImporterCronJob::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_TRIES = 4;

    /**
     * Chunk size.
     */
    #[Service(CustomersImporterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Update existing objects?
     */
    #[Service(CustomersImporterCronJob::class, 'settings.update')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_UPDATE = true;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_CUSTOMERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(CustomersUpdaterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(CustomersUpdaterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(CustomersUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(CustomersUpdaterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_TIMEOUT = self::EP_DATA_LOADER_CUSTOMERS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(CustomersUpdaterCronJob::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_TRIES = self::EP_DATA_LOADER_CUSTOMERS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(CustomersUpdaterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(CustomersUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_ASSETS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(AssetsImporterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(AssetsImporterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(AssetsImporterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(AssetsImporterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_TIMEOUT = 24 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(AssetsImporterCronJob::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_TRIES = 14;

    /**
     * Chunk size.
     */
    #[Service(AssetsImporterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_CHUNK = 500;

    /**
     * Update existing objects?
     */
    #[Service(AssetsImporterCronJob::class, 'settings.update')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_UPDATE = true;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_ASSETS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(AssetsUpdaterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_UPDATER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(AssetsUpdaterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_ASSETS_UPDATER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(AssetsUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_UPDATER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(AssetsUpdaterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_UPDATER_TIMEOUT = self::EP_DATA_LOADER_ASSETS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(AssetsUpdaterCronJob::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_UPDATER_TRIES = self::EP_DATA_LOADER_ASSETS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(AssetsUpdaterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_UPDATER_CHUNK = 500;

    /**
     * Expiration interval.
     */
    #[Service(AssetsUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_ASSETS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DISTRIBUTORS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DistributorsImporterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(DistributorsImporterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DistributorsImporterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DistributorsImporterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TIMEOUT = 1 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DistributorsImporterCronJob::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TRIES = 1;

    /**
     * Chunk size.
     */
    #[Service(DistributorsImporterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Update existing objects?
     */
    #[Service(DistributorsImporterCronJob::class, 'settings.update')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_UPDATE = true;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DISTRIBUTORS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DistributorsUpdaterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(DistributorsUpdaterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DistributorsUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DistributorsUpdaterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_TIMEOUT = self::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DistributorsUpdaterCronJob::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_TRIES = self::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(DistributorsUpdaterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(DistributorsUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DOCUMENTS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DocumentsImporterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(DocumentsImporterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DocumentsImporterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DocumentsImporterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_TIMEOUT = 24 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DocumentsImporterCronJob::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_TRIES = 7;

    /**
     * Chunk size.
     */
    #[Service(DocumentsImporterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK = 500;

    /**
     * Update existing objects?
     */
    #[Service(DocumentsImporterCronJob::class, 'settings.update')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_UPDATE = true;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DOCUMENTS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DocumentsUpdaterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(DocumentsUpdaterCronJob::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DocumentsUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DocumentsUpdaterCronJob::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_TIMEOUT = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DocumentsUpdaterCronJob::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_TRIES = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(DocumentsUpdaterCronJob::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_CHUNK = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(DocumentsUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_DOCUMENTS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH">
    // =========================================================================
    // <editor-fold desc="EP_SEARCH_CUSTOMERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchCustomersUpdaterCronJob::class, 'enabled')]
    #[Group('search')]
    public const EP_SEARCH_CUSTOMERS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchCustomersUpdaterCronJob::class, 'cron')]
    #[Group('search')]
    #[Type(CronExpression::class)]
    public const EP_SEARCH_CUSTOMERS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Queue name.
     */
    #[Service(SearchCustomersUpdaterCronJob::class, 'queue')]
    #[Group('search')]
    public const EP_SEARCH_CUSTOMERS_UPDATER_QUEUE = Queues::SEARCH;

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchCustomersUpdaterCronJob::class, 'timeout')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_CUSTOMERS_UPDATER_TIMEOUT = 6 * 60 * 60;
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH_DOCUMENTS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchDocumentsUpdaterCronJob::class, 'enabled')]
    #[Group('search')]
    public const EP_SEARCH_DOCUMENTS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchDocumentsUpdaterCronJob::class, 'cron')]
    #[Group('search')]
    #[Type(CronExpression::class)]
    public const EP_SEARCH_DOCUMENTS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Queue name.
     */
    #[Service(SearchDocumentsUpdaterCronJob::class, 'queue')]
    #[Group('search')]
    public const EP_SEARCH_DOCUMENTS_UPDATER_QUEUE = Queues::SEARCH;

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchDocumentsUpdaterCronJob::class, 'timeout')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_DOCUMENTS_UPDATER_TIMEOUT = 24 * 60 * 60;
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH_ASSETS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchAssetsUpdaterCronJob::class, 'enabled')]
    #[Group('search')]
    public const EP_SEARCH_ASSETS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchAssetsUpdaterCronJob::class, 'cron')]
    #[Group('search')]
    #[Type(CronExpression::class)]
    public const EP_SEARCH_ASSETS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Queue name.
     */
    #[Service(SearchAssetsUpdaterCronJob::class, 'queue')]
    #[Group('search')]
    public const EP_SEARCH_ASSETS_UPDATER_QUEUE = Queues::SEARCH;

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchAssetsUpdaterCronJob::class, 'timeout')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_ASSETS_UPDATER_TIMEOUT = 24 * 60 * 60;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_JOBS">
    // =========================================================================
    // <editor-fold desc="EP_JOBS_HORIZON_SNAPSHOT">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(QueueSnapshotCronJob::class, 'enabled')]
    #[Group('jobs')]
    public const EP_JOBS_HORIZON_SNAPSHOT_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(QueueSnapshotCronJob::class, 'cron')]
    #[Group('jobs')]
    #[Type(CronExpression::class)]
    public const EP_JOBS_HORIZON_SNAPSHOT_CRON = '*/5 * * * *';

    /**
     * Queue name.
     */
    #[Service(QueueSnapshotCronJob::class, 'queue')]
    #[Group('jobs')]
    public const EP_JOBS_HORIZON_SNAPSHOT_QUEUE = Queues::DEFAULT;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE">
    // =========================================================================
    /**
     * Start time.
     */
    #[Setting('ep.maintenance.start')]
    #[Group('maintenance')]
    #[Type(DateTime::class)]
    public const EP_MAINTENANCE_START = null;

    /**
     * Duration.
     */
    #[Setting('ep.maintenance.duration')]
    #[Group('maintenance')]
    #[Type(Duration::class)]
    public const EP_MAINTENANCE_DURATION = null;

    /**
     * Message.
     */
    #[Setting('ep.maintenance.message')]
    #[Group('maintenance')]
    #[Type(StringType::class)]
    public const EP_MAINTENANCE_MESSAGE = null;

    /**
     * Users who will receive the notifications about maintenance.
     */
    #[Setting('ep.maintenance.notify.users')]
    #[Group('maintenance')]
    #[Type(StringType::class)]
    public const EP_MAINTENANCE_NOTIFY_USERS = [];

    /**
     * Time interval to notify users before maintenance.
     */
    #[Setting('ep.maintenance.notify.before')]
    #[Group('maintenance')]
    #[Type(Duration::class)]
    public const EP_MAINTENANCE_NOTIFY_BEFORE = 'PT1H';

    // <editor-fold desc="EP_MAINTENANCE_ENABLE">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceStartCronJob::class, 'enabled')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_START_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceStartCronJob::class, 'cron')]
    #[Group('maintenance')]
    #[Type(CronExpression::class)]
    public const EP_MAINTENANCE_START_CRON = null;

    /**
     * Queue name.
     */
    #[Service(MaintenanceStartCronJob::class, 'queue')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_START_QUEUE = Queues::DEFAULT;
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE_DISABLE">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceCompleteCronJob::class, 'enabled')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_COMPLETE_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceCompleteCronJob::class, 'cron')]
    #[Group('maintenance')]
    #[Type(CronExpression::class)]
    public const EP_MAINTENANCE_COMPLETE_CRON = null;

    /**
     * Queue name.
     */
    #[Service(MaintenanceCompleteCronJob::class, 'queue')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_COMPLETE_QUEUE = Queues::DEFAULT;
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE_NOTIFY">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceNotifyCronJob::class, 'enabled')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_NOTIFY_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceNotifyCronJob::class, 'cron')]
    #[Group('maintenance')]
    #[Type(CronExpression::class)]
    public const EP_MAINTENANCE_NOTIFY_CRON = null;

    /**
     * Queue name.
     */
    #[Service(MaintenanceNotifyCronJob::class, 'queue')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_NOTIFY_QUEUE = Queues::DEFAULT;
    // </editor-fold>
    // </editor-fold>
}
