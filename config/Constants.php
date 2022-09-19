<?php declare(strict_types = 1);

namespace Config;

use App\CacheStores;
use App\Queues;
use App\Services\DataLoader\Queue\Jobs\AssetsImporter;
use App\Services\DataLoader\Queue\Jobs\AssetsSynchronizer;
use App\Services\DataLoader\Queue\Jobs\CustomersImporter;
use App\Services\DataLoader\Queue\Jobs\CustomersSynchronizer;
use App\Services\DataLoader\Queue\Jobs\DistributorsImporter;
use App\Services\DataLoader\Queue\Jobs\DistributorsSynchronizer;
use App\Services\DataLoader\Queue\Jobs\DocumentsImporter;
use App\Services\DataLoader\Queue\Jobs\DocumentsSynchronizer;
use App\Services\DataLoader\Queue\Jobs\ResellersImporter;
use App\Services\DataLoader\Queue\Jobs\ResellersSynchronizer;
use App\Services\Keycloak\Jobs\Cron\PermissionsSynchronizer;
use App\Services\Keycloak\Jobs\Cron\UsersSynchronizer;
use App\Services\Logger\Logger;
use App\Services\Maintenance\Jobs\CompleteCronJob as MaintenanceCompleteCronJob;
use App\Services\Maintenance\Jobs\NotifyCronJob as MaintenanceNotifyCronJob;
use App\Services\Maintenance\Jobs\StartCronJob as MaintenanceStartCronJob;
use App\Services\Maintenance\Jobs\TelescopeCleaner as MaintenanceTelescopeCleaner;
use App\Services\Recalculator\Queue\Jobs\AssetsRecalculator as RecalculatorAssetsRecalculator;
use App\Services\Recalculator\Queue\Jobs\CustomersRecalculator as RecalculatorCustomersRecalculator;
use App\Services\Recalculator\Queue\Jobs\LocationsRecalculator as RecalculatorLocationsRecalculator;
use App\Services\Recalculator\Queue\Jobs\ResellersRecalculator as RecalculatorResellersRecalculator;
use App\Services\Search\Queue\Jobs\AssetsIndexer as SearchAssetsIndexer;
use App\Services\Search\Queue\Jobs\CustomersIndexer as SearchCustomersIndexer;
use App\Services\Search\Queue\Jobs\DocumentsIndexer as SearchDocumentsIndexer;
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
use App\Services\Settings\Types\DocumentStatus;
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
use RedisException;

/**
 * A list of application settings.
 *
 * Settings priorities:
 * - .env (used only if the application configuration is NOT cached)
 * - this list
 * - other configuration files
 */
interface Constants {
    // <editor-fold desc="APP">
    // =========================================================================
    /**
     * Application name.
     */
    #[Setting]
    #[Group('ep')]
    public const APP_NAME = 'IT Asset Hub';

    /**
     * Application URL
     */
    #[Setting]
    #[Group('ep')]
    public const APP_URL = 'http://localhost';

    /**
     * Debug mode.
     */
    #[Setting]
    #[Group('ep')]
    public const APP_DEBUG = false;
    // </editor-fold>

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
    #[Setting('clockwork.enable')]
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

    // <editor-fold desc="TELESCOPE">
    // =========================================================================
    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_ENABLED = false;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_BATCH_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_CACHE_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_CLIENT_REQUEST_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_COMMAND_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_DUMP_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_EVENT_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_EXCEPTION_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_GATE_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_JOB_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_LOG_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_MAIL_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_MODEL_WATCHER = false;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_NOTIFICATION_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_QUERY_WATCHER = false;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_REDIS_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_REQUEST_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_SCHEDULE_WATCHER = true;

    #[Setting]
    #[Group('telescope')]
    public const TELESCOPE_VIEW_WATCHER = true;

    /**
     * Telescope store all data in memory and will dump it only after the
     * job/command/request is finished. For long-running jobs, this will lead
     * to huge memory usage or even fail.
     *
     * The setting allows to enable Telescope when the total of items is known
     * and less than the setting value.
     */
    #[Setting('ep.telescope.processor.limit')]
    #[Group('telescope')]
    public const EP_TELESCOPE_PROCESSOR_LIMIT = 500;
    // </editor-fold>

    // <editor-fold desc="EP">
    // =========================================================================
    /**
     * Path to the cached `version.php` file.
     */
    #[Setting('ep.version.cache')]
    #[Group('ep')]
    #[Internal]
    #[Type(StringType::class)]
    public const EP_VERSION_CACHE = null;

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
     * If changed Resellers, Customers and Assets must be recalculated!
     */
    #[Setting('ep.contract_types')]
    #[Group('ep')]
    #[Type(DocumentType::class)]
    public const EP_CONTRACT_TYPES = [];

    /**
     * Types IDs related to quotes. Optional, if empty will use IDs which are
     * not in {@see \Config\Constants::EP_CONTRACT_TYPES}.
     *
     * If changed Resellers, Customers and Assets must be recalculated!
     */
    #[Setting('ep.quote_types')]
    #[Group('ep')]
    #[Type(DocumentType::class)]
    public const EP_QUOTE_TYPES = [];

    /**
     * Contracts/Quotes with these Statuses will not be visible on the Portal.
     *
     * If changed Resellers, Customers and Assets must be recalculated!
     */
    #[Setting('ep.document_statuses_hidden')]
    #[Group('ep')]
    #[Type(DocumentStatus::class)]
    public const EP_DOCUMENT_STATUSES_HIDDEN = [];

    /**
     * Price of Contracts/Quotes with these Statuses will not be visible on the Portal.
     */
    #[Setting('ep.document_statuses_no_price')]
    #[Group('ep')]
    #[Type(DocumentStatus::class)]
    public const EP_DOCUMENT_STATUSES_NO_PRICE = [];

    /**
     * Type ID related to headquarter.
     */
    #[Setting('ep.headquarter_type')]
    #[Group('ep')]
    #[Type(LocationType::class)]
    public const EP_HEADQUARTER_TYPE = [];

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
     * Additional email addresses which will receive a copy of all QuoteRequest.
     */
    #[Setting('ep.quote_request.bcc')]
    #[Group('ep')]
    #[Type(Email::class)]
    public const EP_QUOTE_REQUEST_BCC = [];

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

    /**
     * Export: max number of records that can be exported.
     */
    #[Setting('ep.export.limit')]
    #[PublicName('epExportLimit')]
    #[Group('ep')]
    #[Type(IntType::class)]
    public const EP_EXPORT_LIMIT = 100_000;

    /**
     * Export: chunk size.
     */
    #[Setting('ep.export.chunk')]
    #[Group('ep')]
    #[Type(IntType::class)]
    public const EP_EXPORT_CHUNK = null;
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
    public const EP_LOG_SENTRY_LEVEL = PsrLogLevel::WARNING;

    /**
     * Exceptions that will not be sent to Sentry. Some exception like
     * `RedisException` may create a lot of reports and reach the limit very
     * fast. To avoid this you can ignore them by class name.
     */
    #[Setting('ep.log.sentry.ignored_exceptions')]
    #[Group('log')]
    #[Type(StringType::class)]
    public const EP_LOG_SENTRY_IGNORED_EXCEPTIONS = [
        RedisException::class,
    ];

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
     * Services data store (jobs progress, etc).
     */
    #[Setting('ep.cache.service.store')]
    #[Group('cache')]
    #[Internal]
    public const EP_CACHE_SERVICE_STORE = CacheStores::STATE;

    /**
     * Services data TTL (jobs progress, etc).
     */
    #[Setting('ep.cache.service.ttl')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_SERVICE_TTL = 'P6M';

    /**
     * GraphQL Cache enabled?
     */
    #[Setting('ep.cache.graphql.enabled')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_ENABLED = false;

    /**
     * GraphQL Cache store.
     */
    #[Setting('ep.cache.graphql.store')]
    #[Group('cache')]
    #[Internal]
    public const EP_CACHE_GRAPHQL_STORE = CacheStores::PERMANENT;

    /**
     * GraphQL Cache TTL.
     */
    #[Setting('ep.cache.graphql.ttl')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_TTL = 'P2W';

    /**
     * GraphQL time interval inside which the value may become expired.
     *
     * The value will be marked as expired inside this time interval with some
     * probability. Thus, some requests will update the value before real
     * expiration, and it will exclude stepwise server load increase after
     * expiration. The probability increases to the end.
     */
    #[Setting('ep.cache.graphql.ttl_expiration')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_TTL_EXPIRATION = 'P1D';

    /**
     * GraphQL locks enabled?
     *
     * The `@cached` directive tries to reduce server load, so it executes some
     * queries only in one process/request, all other processes/requests
     * just wait.
     *
     * @see \Config\Constants::EP_CACHE_GRAPHQL_LOCK_TIMEOUT
     * @see \Config\Constants::EP_CACHE_GRAPHQL_LOCK_WAIT
     */
    #[Setting('ep.cache.graphql.lock_enabled')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_LOCK_ENABLED = true;

    /**
     * GraphQL lock timeout.
     *
     * These settings determine lock timeout (how long the lock may exist) and a
     * wait timeout (how long another process/request will wait before starting
     * to execute the code by self). Both values should not be bigger than a
     * few minutes.
     *
     * @see \Config\Constants::EP_CACHE_GRAPHQL_LOCK_WAIT
     */
    #[Setting('ep.cache.graphql.lock_timeout')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_LOCK_TIMEOUT = 'PT30S';

    /**
     * GraphQL wait timeout.
     *
     * Should be a bit bigger than lock timeout.
     *
     * @see \Config\Constants::EP_CACHE_GRAPHQL_LOCK_TIMEOUT
     */
    #[Setting('ep.cache.graphql.lock_wait')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_LOCK_WAIT = 'PT35S';

    /**
     * GraphQL lock threshold (seconds with fraction part).
     *
     * Queries faster than this value will not use locks. The `0` disable
     * threshold so all queries will use locks.
     */
    #[Setting('ep.cache.graphql.lock_threshold')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_LOCK_THRESHOLD = 2.5;

    /**
     * GraphQL threshold (seconds with fraction part).
     *
     * Queries faster than this value will not be cached. The `0` disable
     * threshold so all queries will be cached.
     */
    #[Setting('ep.cache.graphql.threshold')]
    #[Group('cache')]
    public const EP_CACHE_GRAPHQL_THRESHOLD = 1.0;

    /**
     * GraphQL minimal lifetime for cached values.
     *
     * Value can be "expired" only after this amount of time. The setting
     * allows reducing the server/db load when the cache expires very often
     * (eg while data importing).
     */
    #[Setting('ep.cache.graphql.lifetime')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_LIFETIME = 'PT1H';

    /**
     * GraphQL time interval inside which the value may become expired.
     *
     * @see \Config\Constants::EP_CACHE_GRAPHQL_LIFETIME
     * @see \Config\Constants::EP_CACHE_GRAPHQL_TTL_EXPIRATION
     */
    #[Setting('ep.cache.graphql.lifetime_expiration')]
    #[Group('cache')]
    #[Type(Duration::class)]
    public const EP_CACHE_GRAPHQL_LIFETIME_EXPIRATION = 'PT1H';
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
     * The URI (can be relative) where user should be redirected after Sign In.
     *
     * Replacements:
     * * `{organization}` - current organization id
     */
    #[Setting('ep.client.signin_uri')]
    #[Group('client')]
    #[Type(StringType::class)]
    public const EP_CLIENT_SIGNIN_URI = 'auth/organizations/{organization}';

    /**
     * The URI (can be relative) where user should be redirected after Sign Out.
     *
     * Replacements:
     * * `{organization}` - current organization id
     */
    #[Setting('ep.client.signout_uri')]
    #[Group('client')]
    #[Type(StringType::class)]
    public const EP_CLIENT_SIGNOUT_URI = 'auth/organizations/{organization}';

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
     * Enabled?
     */
    #[Setting('ep.keycloak.enabled')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_ENABLED = true;

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
     * OrgAdmin Role UUID. You should sync Keycloak permissions via command or
     * job after the setting changed.
     */
    #[Setting('ep.keycloak.org_admin_group')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_ORG_ADMIN_GROUP = null;

    // <editor-fold desc="EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(PermissionsSynchronizer::class, 'enabled')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(PermissionsSynchronizer::class, 'cron')]
    #[Group('keycloak')]
    #[Type(CronExpression::class)]
    public const EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(PermissionsSynchronizer::class, 'queue')]
    #[Group('keycloak')]
    #[Internal]
    public const EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER_QUEUE = Queues::KEYCLOAK;
    // </editor-fold>

    // <editor-fold desc="EP_KEYCLOAK_USERS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(UsersSynchronizer::class, 'enabled')]
    #[Group('keycloak')]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(UsersSynchronizer::class, 'cron')]
    #[Group('keycloak')]
    #[Type(CronExpression::class)]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(UsersSynchronizer::class, 'queue')]
    #[Group('keycloak')]
    #[Internal]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_QUEUE = Queues::KEYCLOAK;

    /**
     * Chunk size.
     */
    #[Service(UsersSynchronizer::class, 'settings.chunk')]
    #[Group('keycloak')]
    #[Type(IntType::class)]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_CHUNK = 100;
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
    #[Internal]
    public const EP_SETTINGS_CONFIG_UPDATE_QUEUE = Queues::SETTINGS;
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
     * GraphQL Endpoint (optional, if empty {@see \Config\Constants::EP_DATA_LOADER_URL} will be used).
     */
    #[Setting('ep.data_loader.endpoint')]
    #[Group('data_loader')]
    #[Type(Url::class)]
    public const EP_DATA_LOADER_ENDPOINT = null;

    /**
     * Resource to access (optional, if empty {@see \Config\Constants::EP_DATA_LOADER_ENDPOINT} will be used).
     */
    #[Setting('ep.data_loader.resource')]
    #[Group('data_loader')]
    #[Type(StringType::class)]
    public const EP_DATA_LOADER_RESOURCE = null;

    /**
     * GraphQL queries that take more than `X` seconds will be logged (set to `0` to disable)
     */
    #[Setting('ep.data_loader.slowlog')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_SLOWLOG = 0;

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
    #[Service(ResellersImporter::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(ResellersImporter::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersImporter::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(ResellersImporter::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_TIMEOUT = 1 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(ResellersImporter::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_TRIES = 1;

    /**
     * Chunk size.
     */
    #[Service(ResellersImporter::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_RESELLERS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersSynchronizer::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(ResellersSynchronizer::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_CRON = '15 0 * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersSynchronizer::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(ResellersSynchronizer::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_RESELLERS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(ResellersSynchronizer::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_RESELLERS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(ResellersSynchronizer::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(ResellersSynchronizer::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Resellers?
     */
    #[Service(ResellersSynchronizer::class, 'settings.outdated')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Resellers to process.
     */
    #[Service(ResellersSynchronizer::class, 'settings.outdated_limit')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_OUTDATED_LIMIT = null;

    /**
     * DateTime/DateInterval when Reseller become outdated.
     */
    #[Service(ResellersSynchronizer::class, 'settings.outdated_expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_OUTDATED_EXPIRE = null;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_CUSTOMERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(CustomersImporter::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(CustomersImporter::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(CustomersImporter::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(CustomersImporter::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_TIMEOUT = 6 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(CustomersImporter::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_TRIES = 4;

    /**
     * Chunk size.
     */
    #[Service(CustomersImporter::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(CustomersSynchronizer::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(CustomersSynchronizer::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_CRON = '30 0 * * *';

    /**
     * Queue name.
     */
    #[Service(CustomersSynchronizer::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(CustomersSynchronizer::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_CUSTOMERS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(CustomersSynchronizer::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_CUSTOMERS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(CustomersSynchronizer::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(CustomersSynchronizer::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Customers?
     */
    #[Service(CustomersSynchronizer::class, 'settings.outdated')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Customers to process.
     */
    #[Service(CustomersSynchronizer::class, 'settings.outdated_limit')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_OUTDATED_LIMIT = null;

    /**
     * DateTime/DateInterval when Customer become outdated.
     */
    #[Service(CustomersSynchronizer::class, 'settings.outdated_expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_OUTDATED_EXPIRE = null;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_ASSETS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(AssetsImporter::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(AssetsImporter::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(AssetsImporter::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(AssetsImporter::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_TIMEOUT = 24 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(AssetsImporter::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_TRIES = 14;

    /**
     * Chunk size.
     */
    #[Service(AssetsImporter::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_CHUNK = 500;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_ASSETS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(AssetsSynchronizer::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(AssetsSynchronizer::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_CRON = '0 1 * * *';

    /**
     * Queue name.
     */
    #[Service(AssetsSynchronizer::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(AssetsSynchronizer::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_ASSETS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(AssetsSynchronizer::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_ASSETS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(AssetsSynchronizer::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_CHUNK = 500;

    /**
     * Expiration interval.
     */
    #[Service(AssetsSynchronizer::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Assets?
     */
    #[Service(AssetsSynchronizer::class, 'settings.outdated')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Assets to process.
     */
    #[Service(AssetsSynchronizer::class, 'settings.outdated_limit')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED_LIMIT = null;

    /**
     * DateTime/DateInterval when Asset become outdated.
     */
    #[Service(AssetsSynchronizer::class, 'settings.outdated_expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED_EXPIRE = null;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DISTRIBUTORS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DistributorsImporter::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(DistributorsImporter::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DistributorsImporter::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DistributorsImporter::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TIMEOUT = 1 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DistributorsImporter::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TRIES = 1;

    /**
     * Chunk size.
     */
    #[Service(DistributorsImporter::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DistributorsSynchronizer::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(DistributorsSynchronizer::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DistributorsSynchronizer::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DistributorsSynchronizer::class, 'timeout')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DistributorsSynchronizer::class, 'tries')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Distributors?
     */
    #[Service(DistributorsSynchronizer::class, 'settings.outdated')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Distributors to process.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.outdated_limit')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_OUTDATED_LIMIT = null;

    /**
     * DateTime/DateInterval when Distributor become outdated.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.outdated_expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_OUTDATED_EXPIRE = null;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DOCUMENTS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DocumentsImporter::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(DocumentsImporter::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(DocumentsImporter::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DocumentsImporter::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_TIMEOUT = 24 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DocumentsImporter::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_TRIES = 7;

    /**
     * Chunk size.
     */
    #[Service(DocumentsImporter::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK = 100;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DocumentsSynchronizer::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(DocumentsSynchronizer::class, 'cron')]
    #[Group('data_loader')]
    #[Type(CronExpression::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_CRON = '0 2 * * *';

    /**
     * Queue name.
     */
    #[Service(DocumentsSynchronizer::class, 'queue')]
    #[Group('data_loader')]
    #[Internal]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_QUEUE = Queues::DATA_LOADER;

    /**
     * Number of seconds the job can run.
     */
    #[Service(DocumentsSynchronizer::class, 'timeout')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DocumentsSynchronizer::class, 'tries')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.chunk')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Documents?
     */
    #[Service(DocumentsSynchronizer::class, 'settings.outdated')]
    #[Group('data_loader')]
    #[Type(BooleanType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Documents to process.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.outdated_limit')]
    #[Group('data_loader')]
    #[Type(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_OUTDATED_LIMIT = null;

    /**
     * DateTime/DateInterval when Document become outdated.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.outdated_expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_OUTDATED_EXPIRE = null;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH">
    // =========================================================================
    /**
     * Elasticsearch url.
     */
    #[Setting]
    #[Group('search')]
    public const EP_SEARCH_URL = 'http://localhost:9200';

    /**
     * Elasticsearch username.
     */
    #[Setting]
    #[Group('search')]
    #[Type(StringType::class)]
    public const EP_SEARCH_USERNAME = null;

    /**
     * Elasticsearch password.
     */
    #[Setting]
    #[Secret]
    #[Group('search')]
    #[Type(StringType::class)]
    public const EP_SEARCH_PASSWORD = null;

    /**
     * Minimal search string length to use FULLTEXT indexes in conditions.
     *
     * The value should be the same as MySQL `ngram_token_size` setting. Please
     * note that after changing the value of `ngram_token_size` you should run
     * `ep:search-fulltext-indexes-rebuild` to recreate FULLTEXT indexes, or
     * you will get unexpected results while filtering.
     *
     * The recommended value is `2`, but you are free for experiments.
     */
    #[Setting('ep.search.fulltext.ngram_token_size')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_FULLTEXT_NGRAM_TOKEN_SIZE = 2;

    /**
     * Minimum severity that should be logged.
     */
    #[Setting]
    #[Group('search')]
    #[Type(LogLevel::class)]
    public const EP_SEARCH_LOG_LEVEL = PsrLogLevel::ERROR;

    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[Group('search')]
    #[Type(Email::class)]
    public const EP_SEARCH_LOG_EMAIL_RECIPIENTS = [];

    // <editor-fold desc="EP_SEARCH_CUSTOMERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchCustomersIndexer::class, 'enabled')]
    #[Group('search')]
    public const EP_SEARCH_CUSTOMERS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchCustomersIndexer::class, 'cron')]
    #[Group('search')]
    #[Type(CronExpression::class)]
    public const EP_SEARCH_CUSTOMERS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Queue name.
     */
    #[Service(SearchCustomersIndexer::class, 'queue')]
    #[Group('search')]
    #[Internal]
    public const EP_SEARCH_CUSTOMERS_UPDATER_QUEUE = Queues::SEARCH;

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchCustomersIndexer::class, 'timeout')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_CUSTOMERS_UPDATER_TIMEOUT = 6 * 60 * 60;
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH_DOCUMENTS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchDocumentsIndexer::class, 'enabled')]
    #[Group('search')]
    public const EP_SEARCH_DOCUMENTS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchDocumentsIndexer::class, 'cron')]
    #[Group('search')]
    #[Type(CronExpression::class)]
    public const EP_SEARCH_DOCUMENTS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Queue name.
     */
    #[Service(SearchDocumentsIndexer::class, 'queue')]
    #[Group('search')]
    #[Internal]
    public const EP_SEARCH_DOCUMENTS_UPDATER_QUEUE = Queues::SEARCH;

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchDocumentsIndexer::class, 'timeout')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_DOCUMENTS_UPDATER_TIMEOUT = 24 * 60 * 60;
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH_ASSETS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchAssetsIndexer::class, 'enabled')]
    #[Group('search')]
    public const EP_SEARCH_ASSETS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchAssetsIndexer::class, 'cron')]
    #[Group('search')]
    #[Type(CronExpression::class)]
    public const EP_SEARCH_ASSETS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Queue name.
     */
    #[Service(SearchAssetsIndexer::class, 'queue')]
    #[Group('search')]
    #[Internal]
    public const EP_SEARCH_ASSETS_UPDATER_QUEUE = Queues::SEARCH;

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchAssetsIndexer::class, 'timeout')]
    #[Group('search')]
    #[Type(IntType::class)]
    public const EP_SEARCH_ASSETS_UPDATER_TIMEOUT = 24 * 60 * 60;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_QUEUE">
    // =========================================================================
    /**
     * Cron expression for `horizon:snapshot`.
     */
    #[Setting('ep.queue.snapshot.cron')]
    #[Group('queue')]
    #[Type(CronExpression::class)]
    public const EP_QUEUE_SNAPSHOT_CRON = '*/5 * * * *';
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
    public const EP_MAINTENANCE_START_CRON = '0 0 1 1 *';

    /**
     * Queue name.
     */
    #[Service(MaintenanceStartCronJob::class, 'queue')]
    #[Group('maintenance')]
    #[Internal]
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
    public const EP_MAINTENANCE_COMPLETE_CRON = '0 0 1 1 *';

    /**
     * Queue name.
     */
    #[Service(MaintenanceCompleteCronJob::class, 'queue')]
    #[Group('maintenance')]
    #[Internal]
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
    public const EP_MAINTENANCE_NOTIFY_CRON = '0 0 1 1 *';

    /**
     * Queue name.
     */
    #[Service(MaintenanceNotifyCronJob::class, 'queue')]
    #[Group('maintenance')]
    #[Internal]
    public const EP_MAINTENANCE_NOTIFY_QUEUE = Queues::DEFAULT;
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE_TELESCOPE_CLEANER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'enabled')]
    #[Group('maintenance')]
    public const EP_MAINTENANCE_TELESCOPE_CLEANER_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'cron')]
    #[Group('maintenance')]
    #[Type(CronExpression::class)]
    public const EP_MAINTENANCE_TELESCOPE_CLEANER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'queue')]
    #[Group('maintenance')]
    #[Internal]
    public const EP_MAINTENANCE_TELESCOPE_CLEANER_QUEUE = Queues::DEFAULT;

    /**
     * Expiration interval.
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'settings.expire')]
    #[Group('maintenance')]
    #[Type(Duration::class)]
    public const EP_MAINTENANCE_TELESCOPE_CLEANER_EXPIRE = 'P1W';
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR">
    // =========================================================================
    // <editor-fold desc="EP_RECALCULATOR_RESELLERS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorResellersRecalculator::class, 'enabled')]
    #[Group('jobs')]
    public const EP_RECALCULATOR_RESELLERS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorResellersRecalculator::class, 'cron')]
    #[Group('jobs')]
    #[Type(CronExpression::class)]
    public const EP_RECALCULATOR_RESELLERS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_CUSTOMERS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorCustomersRecalculator::class, 'enabled')]
    #[Group('jobs')]
    public const EP_RECALCULATOR_CUSTOMERS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorCustomersRecalculator::class, 'cron')]
    #[Group('jobs')]
    #[Type(CronExpression::class)]
    public const EP_RECALCULATOR_CUSTOMERS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_LOCATIONS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorLocationsRecalculator::class, 'enabled')]
    #[Group('jobs')]
    public const EP_RECALCULATOR_LOCATIONS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorLocationsRecalculator::class, 'cron')]
    #[Group('jobs')]
    #[Type(CronExpression::class)]
    public const EP_RECALCULATOR_LOCATIONS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_ASSETS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorAssetsRecalculator::class, 'enabled')]
    #[Group('jobs')]
    public const EP_RECALCULATOR_ASSETS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorAssetsRecalculator::class, 'cron')]
    #[Group('jobs')]
    #[Type(CronExpression::class)]
    public const EP_RECALCULATOR_ASSETS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>
    // </editor-fold>
}
