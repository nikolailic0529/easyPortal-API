<?php declare(strict_types = 1);

namespace Config;

use App\CacheStores;
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
use App\Services\Recalculator\Queue\Jobs\DocumentsRecalculator as RecalculatorDocumentsRecalculator;
use App\Services\Recalculator\Queue\Jobs\LocationsRecalculator as RecalculatorLocationsRecalculator;
use App\Services\Recalculator\Queue\Jobs\ResellersRecalculator as RecalculatorResellersRecalculator;
use App\Services\Search\Queue\Jobs\AssetsIndexer as SearchAssetsIndexer;
use App\Services\Search\Queue\Jobs\CustomersIndexer as SearchCustomersIndexer;
use App\Services\Search\Queue\Jobs\DocumentsIndexer as SearchDocumentsIndexer;
use App\Services\Settings\Attributes\Group as SettingGroup;
use App\Services\Settings\Attributes\Internal as SettingInternal;
use App\Services\Settings\Attributes\PublicName as SettingPublicName;
use App\Services\Settings\Attributes\Secret as SettingSecret;
use App\Services\Settings\Attributes\Service;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Attributes\Type as SettingType;
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
    #[SettingGroup('ep')]
    public const APP_NAME = 'IT Asset Hub';

    /**
     * Application URL
     */
    #[Setting]
    #[SettingGroup('ep')]
    public const APP_URL = 'http://localhost';

    /**
     * Debug mode.
     */
    #[Setting]
    #[SettingGroup('ep')]
    public const APP_DEBUG = false;
    // </editor-fold>

    // <editor-fold desc="MAIL">
    // =========================================================================
    /**
     * Mailer.
     */
    #[Setting]
    #[SettingGroup('mail')]
    public const MAIL_MAILER = 'smtp';

    /**
     * Host.
     */
    #[Setting]
    #[SettingGroup('mail')]
    public const MAIL_HOST = 'smtp-relay.sendinblue.com';

    /**
     * Port.
     */
    #[Setting]
    #[SettingGroup('mail')]
    public const MAIL_PORT = 587;

    /**
     * Username.
     */
    #[Setting]
    #[SettingGroup('mail')]
    public const MAIL_USERNAME = '';

    /**
     * Password.
     */
    #[Setting]
    #[SettingGroup('mail')]
    #[SettingSecret]
    public const MAIL_PASSWORD = '';

    /**
     * Encryption.
     */
    #[Setting]
    #[SettingGroup('mail')]
    #[SettingType(StringType::class)]
    public const MAIL_ENCRYPTION = null;

    /**
     * From address.
     */
    #[Setting]
    #[SettingGroup('mail')]
    public const MAIL_FROM_ADDRESS = 'info@itassethub.test';

    /**
     * From name.
     */
    #[Setting]
    #[SettingGroup('mail')]
    public const MAIL_FROM_NAME = 'IT Asset Hub';
    // </editor-fold>

    // <editor-fold desc="CLOCKWORK">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting('clockwork.enable')]
    #[SettingGroup('clockwork')]
    public const CLOCKWORK_ENABLE = false;

    /**
     * Storage.
     */
    #[Setting]
    #[SettingGroup('clockwork')]
    #[SettingInternal]
    public const CLOCKWORK_STORAGE = 'sql';

    /**
     * Database.
     */
    #[Setting]
    #[SettingGroup('clockwork')]
    #[SettingInternal]
    public const CLOCKWORK_STORAGE_SQL_DATABASE = Logger::CONNECTION;

    /**
     * Maximum lifetime of collected metadata in minutes, older requests will automatically be deleted.
     */
    #[Setting]
    #[SettingGroup('clockwork')]
    public const CLOCKWORK_STORAGE_EXPIRATION = 7 * 24 * 60;
    // </editor-fold>

    // <editor-fold desc="SENTRY">
    // =========================================================================
    /**
     * DSN
     */
    #[Setting]
    #[SettingGroup('sentry')]
    #[SettingType(StringType::class)]
    public const SENTRY_LARAVEL_DSN = null;
    // </editor-fold>

    // <editor-fold desc="TELESCOPE">
    // =========================================================================
    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_ENABLED = false;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_BATCH_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_CACHE_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_CLIENT_REQUEST_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_COMMAND_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_DUMP_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_EVENT_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_EXCEPTION_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_GATE_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_JOB_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_LOG_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_MAIL_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_MODEL_WATCHER = false;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_NOTIFICATION_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_QUERY_WATCHER = false;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_REDIS_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_REQUEST_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_SCHEDULE_WATCHER = true;

    #[Setting]
    #[SettingGroup('telescope')]
    public const TELESCOPE_VIEW_WATCHER = true;

    /**
     * Telescope store all data in memory and will dump it only after the
     * job/command/request is finished. For long-running jobs, this will lead
     * to huge memory usage or even fail.
     *
     * The setting allows to enable Telescope when the total of items is known
     * and less than the setting value. Value `0` disable the limit but not
     * recommended. The `null` or any value below `0` will disable Telescope.
     */
    #[Setting('ep.telescope.processor.limit')]
    #[SettingGroup('telescope')]
    public const EP_TELESCOPE_PROCESSOR_LIMIT = -1;
    // </editor-fold>

    // <editor-fold desc="EP">
    // =========================================================================
    /**
     * Path to the cached `version.php` file.
     */
    #[Setting('ep.version.cache')]
    #[SettingGroup('ep')]
    #[SettingInternal]
    #[SettingType(StringType::class)]
    public const EP_VERSION_CACHE = null;

    /**
     * Max size of branding images/icons (branding_favicon, branding_logo) in KB.
     */
    #[Setting('ep.image.max_size')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epImageMaxSize')]
    public const EP_IMAGE_MAX_SIZE = 2048;

    /**
     * Accepted image formats.
     */
    #[Setting('ep.image.formats')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epImageFormats')]
    #[SettingType(StringType::class)]
    public const EP_IMAGE_FORMATS = ['jpg', 'jpeg', 'png'];

    /**
     * Type IDs related to contracts.
     *
     * If changed, Resellers, Customers, Documents and Assets (in this order)
     * must be recalculated!
     */
    #[Setting('ep.contract_types')]
    #[SettingGroup('ep')]
    #[SettingType(DocumentType::class)]
    public const EP_CONTRACT_TYPES = [];

    /**
     * Types IDs related to quotes. Optional, if empty will use IDs which are
     * not in {@see \Config\Constants::EP_CONTRACT_TYPES}.
     *
     * If changed, Resellers, Customers, Documents and Assets (in this order)
     * must be recalculated!
     */
    #[Setting('ep.quote_types')]
    #[SettingGroup('ep')]
    #[SettingType(DocumentType::class)]
    public const EP_QUOTE_TYPES = [];

    /**
     * Contracts/Quotes with these Statuses will not be visible on the Portal.
     *
     * If changed, Resellers, Customers, Documents and Assets (in this order)
     * must be recalculated!
     */
    #[Setting('ep.document_statuses_hidden')]
    #[SettingGroup('ep')]
    #[SettingType(DocumentStatus::class)]
    public const EP_DOCUMENT_STATUSES_HIDDEN = [];

    /**
     * Price of Contracts/Quotes with these Statuses will not be visible on the Portal.
     *
     * If changed Documents must be recalculated!
     */
    #[Setting('ep.document_statuses_no_price')]
    #[SettingGroup('ep')]
    #[SettingType(DocumentStatus::class)]
    public const EP_DOCUMENT_STATUSES_NO_PRICE = [];

    /**
     * Type ID related to headquarter.
     */
    #[Setting('ep.headquarter_type')]
    #[SettingGroup('ep')]
    #[SettingType(LocationType::class)]
    public const EP_HEADQUARTER_TYPE = [];

    /**
     * Root organization ID.
     */
    #[Setting('ep.root_organization')]
    #[SettingGroup('ep')]
    #[SettingType(Organization::class)]
    public const EP_ROOT_ORGANIZATION = '40765bbb-4736-4d2f-8964-1c3fd4e59aac';

    /**
     * Max size of uploaded files in KB.
     */
    #[Setting('ep.file.max_size')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epFileMaxSize')]
    public const EP_FILE_MAX_SIZE = 2048;

    /**
     * Accepted file/document formats.
     */
    #[Setting('ep.file.formats')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epFileFormats')]
    #[SettingType(StringType::class)]
    public const EP_FILE_FORMATS = ['jpg', 'jpeg', 'png', 'csv', 'xlsx', 'pdf', 'docx', 'doc'];

    /**
     * Tesedi Portal Email Address.
     */
    #[Setting('ep.email_address')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epEmailAddress')]
    #[SettingType(Email::class)]
    public const EP_EMAIL_ADDRESS = 'info@itassethub.test';

    /**
     * Additional email addresses which will receive a copy of all QuoteRequest.
     */
    #[Setting('ep.quote_request.bcc')]
    #[SettingGroup('ep')]
    #[SettingType(Email::class)]
    public const EP_QUOTE_REQUEST_BCC = [];

    /**
     * Invitation expiration duration.
     */
    #[Setting('ep.invite_expire')]
    #[SettingGroup('ep')]
    #[SettingType(Duration::class)]
    public const EP_INVITE_EXPIRE = 'PT24H';

    /**
     * Pagination: Default value for `limit`.
     */
    #[Setting('ep.pagination.limit.default')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epPaginationLimitDefault')]
    #[SettingType(IntType::class)]
    public const EP_PAGINATION_LIMIT_DEFAULT = 25;

    /**
     * Pagination: Max allowed value of `limit`.
     */
    #[Setting('ep.pagination.limit.max')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epPaginationLimitMax')]
    #[SettingType(IntType::class)]
    public const EP_PAGINATION_LIMIT_MAX = 100;

    /**
     * Export: max number of records that can be exported.
     */
    #[Setting('ep.export.limit')]
    #[SettingGroup('ep')]
    #[SettingPublicName('epExportLimit')]
    #[SettingType(IntType::class)]
    public const EP_EXPORT_LIMIT = 100_000;

    /**
     * Export: chunk size.
     */
    #[Setting('ep.export.chunk')]
    #[SettingGroup('ep')]
    #[SettingType(IntType::class)]
    public const EP_EXPORT_CHUNK = null;
    // </editor-fold>

    // <editor-fold desc="EP_LOG">
    // =========================================================================
    /**
     * Minimum severity that should be logged.
     */
    #[Setting]
    #[SettingGroup('log')]
    #[SettingType(LogLevel::class)]
    public const LOG_LEVEL = PsrLogLevel::DEBUG;

    /**
     * Send errors to Sentry?
     */
    #[Setting]
    #[SettingGroup('log')]
    public const EP_LOG_SENTRY_ENABLED = false;

    /**
     * Minimum severity that should be logged via Sentry.
     */
    #[Setting]
    #[SettingGroup('log')]
    #[SettingType(LogLevel::class)]
    public const EP_LOG_SENTRY_LEVEL = PsrLogLevel::WARNING;

    /**
     * Exceptions that will not be sent to Sentry. Some exception like
     * `RedisException` may create a lot of reports and reach the limit very
     * fast. To avoid this you can ignore them by class name.
     */
    #[Setting('ep.log.sentry.ignored_exceptions')]
    #[SettingGroup('log')]
    #[SettingType(StringType::class)]
    public const EP_LOG_SENTRY_IGNORED_EXCEPTIONS = [
        RedisException::class,
    ];

    /**
     * Send errors to emails?
     */
    #[Setting]
    #[SettingGroup('log')]
    public const EP_LOG_EMAIL_ENABLED = false;

    /**
     * Minimum severity that should be logged via emails.
     */
    #[Setting]
    #[SettingGroup('log')]
    #[SettingType(LogLevel::class)]
    public const EP_LOG_EMAIL_LEVEL = PsrLogLevel::ERROR;

    /**
     * Email addresses that will receive errors.
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[SettingGroup('log')]
    #[SettingType(Email::class)]
    public const EP_LOG_EMAIL_RECIPIENTS = ['chief.wraith+notice@gmail.com'];
    // </editor-fold>

    // <editor-fold desc="EP_CACHE">
    // =========================================================================
    /**
     * Services data store (jobs progress, etc).
     */
    #[Setting('ep.cache.service.store')]
    #[SettingGroup('cache')]
    #[SettingInternal]
    public const EP_CACHE_SERVICE_STORE = CacheStores::STATE;

    /**
     * Services data TTL (jobs progress, etc).
     */
    #[Setting('ep.cache.service.ttl')]
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
    public const EP_CACHE_SERVICE_TTL = 'P6M';

    /**
     * GraphQL Cache enabled?
     */
    #[Setting('ep.cache.graphql.enabled')]
    #[SettingGroup('cache')]
    public const EP_CACHE_GRAPHQL_ENABLED = false;

    /**
     * GraphQL Cache store.
     */
    #[Setting('ep.cache.graphql.store')]
    #[SettingGroup('cache')]
    #[SettingInternal]
    public const EP_CACHE_GRAPHQL_STORE = CacheStores::PERMANENT;

    /**
     * GraphQL Cache TTL.
     */
    #[Setting('ep.cache.graphql.ttl')]
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
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
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
    public const EP_CACHE_GRAPHQL_TTL_EXPIRATION = 'P1D';

    /**
     * GraphQL locks enabled?
     *
     * The `@cached` directive tries to reduce server load, so it executes some
     * queries only in one process/request, all other processes/requests
     * just wait.
     *
     * @see Constants::EP_CACHE_GRAPHQL_LOCK_TIMEOUT
     * @see Constants::EP_CACHE_GRAPHQL_LOCK_WAIT
     */
    #[Setting('ep.cache.graphql.lock_enabled')]
    #[SettingGroup('cache')]
    public const EP_CACHE_GRAPHQL_LOCK_ENABLED = true;

    /**
     * GraphQL lock timeout.
     *
     * These settings determine lock timeout (how long the lock may exist) and a
     * wait timeout (how long another process/request will wait before starting
     * to execute the code by self). Both values should not be bigger than a
     * few minutes.
     *
     * @see Constants::EP_CACHE_GRAPHQL_LOCK_WAIT
     */
    #[Setting('ep.cache.graphql.lock_timeout')]
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
    public const EP_CACHE_GRAPHQL_LOCK_TIMEOUT = 'PT30S';

    /**
     * GraphQL wait timeout.
     *
     * Should be a bit bigger than lock timeout.
     *
     * @see Constants::EP_CACHE_GRAPHQL_LOCK_TIMEOUT
     */
    #[Setting('ep.cache.graphql.lock_wait')]
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
    public const EP_CACHE_GRAPHQL_LOCK_WAIT = 'PT35S';

    /**
     * GraphQL lock threshold (seconds with fraction part).
     *
     * Queries faster than this value will not use locks. The `0` disable
     * threshold so all queries will use locks.
     */
    #[Setting('ep.cache.graphql.lock_threshold')]
    #[SettingGroup('cache')]
    public const EP_CACHE_GRAPHQL_LOCK_THRESHOLD = 2.5;

    /**
     * GraphQL threshold (seconds with fraction part).
     *
     * Queries faster than this value will not be cached. The `0` disable
     * threshold so all queries will be cached.
     */
    #[Setting('ep.cache.graphql.threshold')]
    #[SettingGroup('cache')]
    public const EP_CACHE_GRAPHQL_THRESHOLD = 1.0;

    /**
     * GraphQL minimal lifetime for cached values.
     *
     * Value can be "expired" only after this amount of time. The setting
     * allows reducing the server/db load when the cache expires very often
     * (eg while data importing).
     */
    #[Setting('ep.cache.graphql.lifetime')]
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
    public const EP_CACHE_GRAPHQL_LIFETIME = 'PT1H';

    /**
     * GraphQL time interval inside which the value may become expired.
     *
     * @see Constants::EP_CACHE_GRAPHQL_LIFETIME
     * @see Constants::EP_CACHE_GRAPHQL_TTL_EXPIRATION
     */
    #[Setting('ep.cache.graphql.lifetime_expiration')]
    #[SettingGroup('cache')]
    #[SettingType(Duration::class)]
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
    #[SettingGroup('auth')]
    #[SettingType(Email::class)]
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
    #[SettingGroup('client')]
    #[SettingType(StringType::class)]
    public const EP_CLIENT_SIGNIN_URI = 'auth/organizations/{organization}';

    /**
     * The URI (can be relative) where user should be redirected after Sign Out.
     *
     * Replacements:
     * * `{organization}` - current organization id
     */
    #[Setting('ep.client.signout_uri')]
    #[SettingGroup('client')]
    #[SettingType(StringType::class)]
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
    #[SettingGroup('client')]
    #[SettingType(StringType::class)]
    public const EP_CLIENT_PASSWORD_RESET_URI = 'auth/reset-password/{token}?email={email}';

    /**
     * The URI (can be relative) where user should be redirected to complete
     * Sign Up by invitation.
     *
     * Replacements:
     * * `{token}` - token (required)
     */
    #[Setting('ep.client.invite_uri')]
    #[SettingGroup('client')]
    #[SettingType(StringType::class)]
    public const EP_CLIENT_INVITE_URI = 'auth/signup/{token}';
    //</editor-fold>

    // <editor-fold desc="EP_KEYCLOAK">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting('ep.keycloak.enabled')]
    #[SettingGroup('keycloak')]
    public const EP_KEYCLOAK_ENABLED = true;

    /**
     * Server URL.
     */
    #[Setting('ep.keycloak.url')]
    #[SettingGroup('keycloak')]
    #[SettingType(StringType::class)]
    public const EP_KEYCLOAK_URL = null;

    /**
     * Realm.
     */
    #[Setting('ep.keycloak.realm')]
    #[SettingGroup('keycloak')]
    #[SettingType(StringType::class)]
    public const EP_KEYCLOAK_REALM = null;

    /**
     * Client Id.
     */
    #[Setting('ep.keycloak.client_id')]
    #[SettingGroup('keycloak')]
    #[SettingType(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_ID = null;

    /**
     * Keycloak client uuid.
     */
    #[Setting('ep.keycloak.client_uuid')]
    #[SettingGroup('keycloak')]
    #[SettingType(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_UUID = null;

    /**
     * Client Secret.
     */
    #[Setting('ep.keycloak.client_secret')]
    #[SettingGroup('keycloak')]
    #[SettingSecret]
    #[SettingType(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_SECRET = null;

    /**
     * Encryption Algorithm.
     */
    #[Setting('ep.keycloak.encryption.algorithm')]
    #[SettingGroup('keycloak')]
    public const EP_KEYCLOAK_ENCRYPTION_ALGORITHM = 'RS256';

    /**
     * Encryption Public Key.
     */
    #[Setting('ep.keycloak.encryption.public_key')]
    #[SettingGroup('keycloak')]
    #[SettingType(Text::class)]
    public const EP_KEYCLOAK_ENCRYPTION_PUBLIC_KEY = '';

    /**
     * Leeway for JWT validation.
     */
    #[Setting('ep.keycloak.leeway')]
    #[SettingGroup('keycloak')]
    #[SettingType(Duration::class)]
    public const EP_KEYCLOAK_LEEWAY = null;

    /**
     * Default timeout for http requests (in seconds).
     */
    #[Setting('ep.keycloak.timeout')]
    #[SettingGroup('keycloak')]
    public const EP_KEYCLOAK_TIMEOUT = 5 * 60;

    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[SettingGroup('keycloak')]
    #[SettingType(Email::class)]
    public const EP_KEYCLOAK_LOG_EMAIL_RECIPIENTS = [];

    /**
     * OrgAdmin Role UUID. You should sync Keycloak permissions via command or
     * job after the setting changed.
     */
    #[Setting('ep.keycloak.org_admin_group')]
    #[SettingGroup('keycloak')]
    #[SettingType(StringType::class)]
    public const EP_KEYCLOAK_ORG_ADMIN_GROUP = null;

    // <editor-fold desc="EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(PermissionsSynchronizer::class, 'enabled')]
    #[SettingGroup('keycloak')]
    public const EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(PermissionsSynchronizer::class, 'cron')]
    #[SettingGroup('keycloak')]
    #[SettingType(CronExpression::class)]
    public const EP_KEYCLOAK_PERMISSIONS_SYNCHRONIZER_CRON = '0 0 * * *';
    // </editor-fold>

    // <editor-fold desc="EP_KEYCLOAK_USERS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(UsersSynchronizer::class, 'enabled')]
    #[SettingGroup('keycloak')]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(UsersSynchronizer::class, 'cron')]
    #[SettingGroup('keycloak')]
    #[SettingType(CronExpression::class)]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_CRON = '0 0 * * *';

    /**
     * Chunk size.
     */
    #[Service(UsersSynchronizer::class, 'settings.chunk')]
    #[SettingGroup('keycloak')]
    #[SettingType(IntType::class)]
    public const EP_KEYCLOAK_USERS_SYNCHRONIZER_CHUNK = 100;
    // </editor-fold>

    // </editor-fold>

    // <editor-fold desc="EP_LOGGER">
    // =========================================================================
    /**
     * Logger enabled?
     */
    #[Setting('ep.logger.enabled')]
    #[SettingGroup('logger')]
    public const EP_LOGGER_ENABLED = false;

    /**
     * How often long-running actions should be dumped?
     */
    #[Setting('ep.logger.dump')]
    #[SettingGroup('logger')]
    #[SettingType(Duration::class)]
    public const EP_LOGGER_DUMP = 'PT5M';

    /**
     * Log models changes?
     */
    #[Setting('ep.logger.eloquent.models')]
    #[SettingGroup('logger')]
    public const EP_LOGGER_ELOQUENT_MODELS = false;

    /**
     * Log DataLoader queries?
     */
    #[Setting('ep.logger.data_loader.queries')]
    #[SettingGroup('logger')]
    public const EP_LOGGER_DATA_LOADER_QUERIES = false;

    /**
     * Log DataLoader mutations?
     */
    #[Setting('ep.logger.data_loader.mutations')]
    #[SettingGroup('logger')]
    public const EP_LOGGER_DATA_LOADER_MUTATIONS = false;
    //</editor-fold>

    // <editor-fold desc="EP_DATA_LOADER">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting('ep.data_loader.enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ENABLED = true;

    /**
     * URL.
     */
    #[Setting('ep.data_loader.url')]
    #[SettingGroup('data_loader')]
    #[SettingType(Url::class)]
    public const EP_DATA_LOADER_URL = '';

    /**
     * Client ID.
     */
    #[Setting('ep.data_loader.client_id')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_CLIENT_ID = '';

    /**
     * Client Secret.
     */
    #[Setting('ep.data_loader.client_secret')]
    #[SettingGroup('data_loader')]
    #[SettingSecret]
    public const EP_DATA_LOADER_CLIENT_SECRET = '';

    /**
     * Default chunk size.
     */
    #[Setting('ep.data_loader.chunk')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_CHUNK = 250;

    /**
     * Default timeout for http requests (in seconds).
     */
    #[Setting('ep.data_loader.timeout')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_TIMEOUT = 5 * 60;

    /**
     * GraphQL Endpoint (optional, if empty {@see \Config\Constants::EP_DATA_LOADER_URL} will be used).
     */
    #[Setting('ep.data_loader.endpoint')]
    #[SettingGroup('data_loader')]
    #[SettingType(Url::class)]
    public const EP_DATA_LOADER_ENDPOINT = null;

    /**
     * Resource to access (optional, if empty {@see \Config\Constants::EP_DATA_LOADER_ENDPOINT} will be used).
     */
    #[Setting('ep.data_loader.resource')]
    #[SettingGroup('data_loader')]
    #[SettingType(StringType::class)]
    public const EP_DATA_LOADER_RESOURCE = null;

    /**
     * GraphQL queries that take more than `X` seconds will be logged (set to `0` to disable)
     */
    #[Setting('ep.data_loader.slowlog')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_SLOWLOG = 0;

    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[SettingGroup('data_loader')]
    #[SettingType(Email::class)]
    public const EP_DATA_LOADER_LOG_EMAIL_RECIPIENTS = [];

    // <editor-fold desc="EP_DATA_LOADER_RESELLERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersImporter::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(ResellersImporter::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(ResellersImporter::class, 'timeout')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_TIMEOUT = 1 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(ResellersImporter::class, 'tries')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_TRIES = 1;

    /**
     * Chunk size.
     */
    #[Service(ResellersImporter::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_RESELLERS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersSynchronizer::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(ResellersSynchronizer::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_CRON = '15 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(ResellersSynchronizer::class, 'timeout')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_RESELLERS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(ResellersSynchronizer::class, 'tries')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_RESELLERS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(ResellersSynchronizer::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(ResellersSynchronizer::class, 'settings.expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Resellers?
     */
    #[Service(ResellersSynchronizer::class, 'settings.outdated')]
    #[SettingGroup('data_loader')]
    #[SettingType(BooleanType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Resellers to process.
     */
    #[Service(ResellersSynchronizer::class, 'settings.outdated_limit')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_OUTDATED_LIMIT = 50;

    /**
     * DateTime/DateInterval when Reseller become outdated.
     */
    #[Service(ResellersSynchronizer::class, 'settings.outdated_expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_RESELLERS_SYNCHRONIZER_OUTDATED_EXPIRE = 'P1W';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_CUSTOMERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(CustomersImporter::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(CustomersImporter::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(CustomersImporter::class, 'timeout')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_TIMEOUT = 6 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(CustomersImporter::class, 'tries')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_TRIES = 4;

    /**
     * Chunk size.
     */
    #[Service(CustomersImporter::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(CustomersSynchronizer::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(CustomersSynchronizer::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_CRON = '30 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(CustomersSynchronizer::class, 'timeout')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_CUSTOMERS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(CustomersSynchronizer::class, 'tries')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_CUSTOMERS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(CustomersSynchronizer::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(CustomersSynchronizer::class, 'settings.expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Customers?
     */
    #[Service(CustomersSynchronizer::class, 'settings.outdated')]
    #[SettingGroup('data_loader')]
    #[SettingType(BooleanType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Customers to process.
     */
    #[Service(CustomersSynchronizer::class, 'settings.outdated_limit')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_OUTDATED_LIMIT = 75;

    /**
     * DateTime/DateInterval when Customer become outdated.
     */
    #[Service(CustomersSynchronizer::class, 'settings.outdated_expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_CUSTOMERS_SYNCHRONIZER_OUTDATED_EXPIRE = 'P1W';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_ASSETS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(AssetsImporter::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(AssetsImporter::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(AssetsImporter::class, 'timeout')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_TIMEOUT = 24 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(AssetsImporter::class, 'tries')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_TRIES = 14;

    /**
     * Chunk size.
     */
    #[Service(AssetsImporter::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_IMPORTER_CHUNK = 500;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_ASSETS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(AssetsSynchronizer::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(AssetsSynchronizer::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_CRON = '0 1 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(AssetsSynchronizer::class, 'timeout')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_ASSETS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(AssetsSynchronizer::class, 'tries')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_ASSETS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(AssetsSynchronizer::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_CHUNK = 500;

    /**
     * Expiration interval.
     */
    #[Service(AssetsSynchronizer::class, 'settings.expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Assets?
     */
    #[Service(AssetsSynchronizer::class, 'settings.outdated')]
    #[SettingGroup('data_loader')]
    #[SettingType(BooleanType::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Assets to process.
     */
    #[Service(AssetsSynchronizer::class, 'settings.outdated_limit')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED_LIMIT = 275;

    /**
     * DateTime/DateInterval when Asset become outdated.
     */
    #[Service(AssetsSynchronizer::class, 'settings.outdated_expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_ASSETS_SYNCHRONIZER_OUTDATED_EXPIRE = 'P1M';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DISTRIBUTORS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DistributorsImporter::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(DistributorsImporter::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(DistributorsImporter::class, 'timeout')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TIMEOUT = 1 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DistributorsImporter::class, 'tries')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TRIES = 1;

    /**
     * Chunk size.
     */
    #[Service(DistributorsImporter::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_CHUNK = self::EP_DATA_LOADER_CHUNK;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DistributorsSynchronizer::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(DistributorsSynchronizer::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_CRON = '0 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(DistributorsSynchronizer::class, 'timeout')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DistributorsSynchronizer::class, 'tries')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_DISTRIBUTORS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Distributors?
     */
    #[Service(DistributorsSynchronizer::class, 'settings.outdated')]
    #[SettingGroup('data_loader')]
    #[SettingType(BooleanType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Distributors to process.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.outdated_limit')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_OUTDATED_LIMIT = null;

    /**
     * DateTime/DateInterval when Distributor become outdated.
     */
    #[Service(DistributorsSynchronizer::class, 'settings.outdated_expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_DISTRIBUTORS_SYNCHRONIZER_OUTDATED_EXPIRE = 'P1W';
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DOCUMENTS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DocumentsImporter::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(DocumentsImporter::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(DocumentsImporter::class, 'timeout')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_TIMEOUT = 24 * 60 * 60;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DocumentsImporter::class, 'tries')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_TRIES = 7;

    /**
     * Chunk size.
     */
    #[Service(DocumentsImporter::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK = 100;
    // </editor-fold>

    // <editor-fold desc="EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(DocumentsSynchronizer::class, 'enabled')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_ENABLED = self::EP_DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(DocumentsSynchronizer::class, 'cron')]
    #[SettingGroup('data_loader')]
    #[SettingType(CronExpression::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_CRON = '0 2 * * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(DocumentsSynchronizer::class, 'timeout')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_TIMEOUT = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_TIMEOUT;

    /**
     * Number of times the job may be attempted.
     */
    #[Service(DocumentsSynchronizer::class, 'tries')]
    #[SettingGroup('data_loader')]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_TRIES = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_TRIES;

    /**
     * Chunk size.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.chunk')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_CHUNK = self::EP_DATA_LOADER_DOCUMENTS_IMPORTER_CHUNK;

    /**
     * Expiration interval.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_EXPIRE = 'PT24H';

    /**
     * Process outdated Documents?
     */
    #[Service(DocumentsSynchronizer::class, 'settings.outdated')]
    #[SettingGroup('data_loader')]
    #[SettingType(BooleanType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_OUTDATED = false;

    /**
     * Maximum number of outdated Documents to process.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.outdated_limit')]
    #[SettingGroup('data_loader')]
    #[SettingType(IntType::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_OUTDATED_LIMIT = 50;

    /**
     * DateTime/DateInterval when Document become outdated.
     */
    #[Service(DocumentsSynchronizer::class, 'settings.outdated_expire')]
    #[SettingGroup('data_loader')]
    #[SettingType(Duration::class)]
    public const EP_DATA_LOADER_DOCUMENTS_SYNCHRONIZER_OUTDATED_EXPIRE = 'P1M';
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH">
    // =========================================================================
    /**
     * Elasticsearch url.
     */
    #[Setting]
    #[SettingGroup('search')]
    public const EP_SEARCH_URL = 'http://localhost:9200';

    /**
     * Elasticsearch username.
     */
    #[Setting]
    #[SettingGroup('search')]
    #[SettingType(StringType::class)]
    public const EP_SEARCH_USERNAME = null;

    /**
     * Elasticsearch password.
     */
    #[Setting]
    #[SettingGroup('search')]
    #[SettingSecret]
    #[SettingType(StringType::class)]
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
    #[SettingGroup('search')]
    #[SettingType(IntType::class)]
    public const EP_SEARCH_FULLTEXT_NGRAM_TOKEN_SIZE = 2;

    /**
     * Minimum severity that should be logged.
     */
    #[Setting]
    #[SettingGroup('search')]
    #[SettingType(LogLevel::class)]
    public const EP_SEARCH_LOG_LEVEL = PsrLogLevel::ERROR;

    /**
     * Email addresses that will receive errors (overwrites default setting).
     *
     * You can use subaddressing to specify desired error level, eg
     * `test+warning@example.com` will receive "warning", "error" and up but
     * not "info", "notice".
     */
    #[Setting]
    #[SettingGroup('search')]
    #[SettingType(Email::class)]
    public const EP_SEARCH_LOG_EMAIL_RECIPIENTS = [];

    // <editor-fold desc="EP_SEARCH_CUSTOMERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchCustomersIndexer::class, 'enabled')]
    #[SettingGroup('search')]
    public const EP_SEARCH_CUSTOMERS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchCustomersIndexer::class, 'cron')]
    #[SettingGroup('search')]
    #[SettingType(CronExpression::class)]
    public const EP_SEARCH_CUSTOMERS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchCustomersIndexer::class, 'timeout')]
    #[SettingGroup('search')]
    #[SettingType(IntType::class)]
    public const EP_SEARCH_CUSTOMERS_UPDATER_TIMEOUT = 6 * 60 * 60;
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH_DOCUMENTS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchDocumentsIndexer::class, 'enabled')]
    #[SettingGroup('search')]
    public const EP_SEARCH_DOCUMENTS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchDocumentsIndexer::class, 'cron')]
    #[SettingGroup('search')]
    #[SettingType(CronExpression::class)]
    public const EP_SEARCH_DOCUMENTS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchDocumentsIndexer::class, 'timeout')]
    #[SettingGroup('search')]
    #[SettingType(IntType::class)]
    public const EP_SEARCH_DOCUMENTS_UPDATER_TIMEOUT = 24 * 60 * 60;
    // </editor-fold>

    // <editor-fold desc="EP_SEARCH_ASSETS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled? Probably should be disabled. The job runs automatically if needed.
     */
    #[Service(SearchAssetsIndexer::class, 'enabled')]
    #[SettingGroup('search')]
    public const EP_SEARCH_ASSETS_UPDATER_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(SearchAssetsIndexer::class, 'cron')]
    #[SettingGroup('search')]
    #[SettingType(CronExpression::class)]
    public const EP_SEARCH_ASSETS_UPDATER_CRON = '0 0 1 * *';

    /**
     * Number of seconds the job can run.
     */
    #[Service(SearchAssetsIndexer::class, 'timeout')]
    #[SettingGroup('search')]
    #[SettingType(IntType::class)]
    public const EP_SEARCH_ASSETS_UPDATER_TIMEOUT = 24 * 60 * 60;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="EP_QUEUE">
    // =========================================================================
    /**
     * Cron expression for `horizon:snapshot`.
     */
    #[Setting('ep.queue.snapshot.cron')]
    #[SettingGroup('queue')]
    #[SettingType(CronExpression::class)]
    public const EP_QUEUE_SNAPSHOT_CRON = '*/5 * * * *';
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE">
    // =========================================================================
    /**
     * Start time.
     */
    #[Setting('ep.maintenance.start')]
    #[SettingGroup('maintenance')]
    #[SettingType(DateTime::class)]
    public const EP_MAINTENANCE_START = null;

    /**
     * Duration.
     */
    #[Setting('ep.maintenance.duration')]
    #[SettingGroup('maintenance')]
    #[SettingType(Duration::class)]
    public const EP_MAINTENANCE_DURATION = null;

    /**
     * Message.
     */
    #[Setting('ep.maintenance.message')]
    #[SettingGroup('maintenance')]
    #[SettingType(StringType::class)]
    public const EP_MAINTENANCE_MESSAGE = null;

    /**
     * Users who will receive the notifications about maintenance.
     */
    #[Setting('ep.maintenance.notify.users')]
    #[SettingGroup('maintenance')]
    #[SettingType(StringType::class)]
    public const EP_MAINTENANCE_NOTIFY_USERS = [];

    /**
     * Time interval to notify users before maintenance.
     */
    #[Setting('ep.maintenance.notify.before')]
    #[SettingGroup('maintenance')]
    #[SettingType(Duration::class)]
    public const EP_MAINTENANCE_NOTIFY_BEFORE = 'PT1H';

    // <editor-fold desc="EP_MAINTENANCE_ENABLE">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceStartCronJob::class, 'enabled')]
    #[SettingGroup('maintenance')]
    public const EP_MAINTENANCE_START_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceStartCronJob::class, 'cron')]
    #[SettingGroup('maintenance')]
    #[SettingType(CronExpression::class)]
    public const EP_MAINTENANCE_START_CRON = '0 0 1 1 *';
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE_DISABLE">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceCompleteCronJob::class, 'enabled')]
    #[SettingGroup('maintenance')]
    public const EP_MAINTENANCE_COMPLETE_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceCompleteCronJob::class, 'cron')]
    #[SettingGroup('maintenance')]
    #[SettingType(CronExpression::class)]
    public const EP_MAINTENANCE_COMPLETE_CRON = '0 0 1 1 *';
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE_NOTIFY">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceNotifyCronJob::class, 'enabled')]
    #[SettingGroup('maintenance')]
    public const EP_MAINTENANCE_NOTIFY_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceNotifyCronJob::class, 'cron')]
    #[SettingGroup('maintenance')]
    #[SettingType(CronExpression::class)]
    public const EP_MAINTENANCE_NOTIFY_CRON = '0 0 1 1 *';
    // </editor-fold>

    // <editor-fold desc="EP_MAINTENANCE_TELESCOPE_CLEANER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'enabled')]
    #[SettingGroup('maintenance')]
    public const EP_MAINTENANCE_TELESCOPE_CLEANER_ENABLED = true;

    /**
     * Cron expression.
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'cron')]
    #[SettingGroup('maintenance')]
    #[SettingType(CronExpression::class)]
    public const EP_MAINTENANCE_TELESCOPE_CLEANER_CRON = '0 0 * * *';

    /**
     * Expiration interval.
     */
    #[Service(MaintenanceTelescopeCleaner::class, 'settings.expire')]
    #[SettingGroup('maintenance')]
    #[SettingType(Duration::class)]
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
    #[SettingGroup('jobs')]
    public const EP_RECALCULATOR_RESELLERS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorResellersRecalculator::class, 'cron')]
    #[SettingGroup('jobs')]
    #[SettingType(CronExpression::class)]
    public const EP_RECALCULATOR_RESELLERS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_CUSTOMERS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorCustomersRecalculator::class, 'enabled')]
    #[SettingGroup('jobs')]
    public const EP_RECALCULATOR_CUSTOMERS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorCustomersRecalculator::class, 'cron')]
    #[SettingGroup('jobs')]
    #[SettingType(CronExpression::class)]
    public const EP_RECALCULATOR_CUSTOMERS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_LOCATIONS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorLocationsRecalculator::class, 'enabled')]
    #[SettingGroup('jobs')]
    public const EP_RECALCULATOR_LOCATIONS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorLocationsRecalculator::class, 'cron')]
    #[SettingGroup('jobs')]
    #[SettingType(CronExpression::class)]
    public const EP_RECALCULATOR_LOCATIONS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_ASSETS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorAssetsRecalculator::class, 'enabled')]
    #[SettingGroup('jobs')]
    public const EP_RECALCULATOR_ASSETS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorAssetsRecalculator::class, 'cron')]
    #[SettingGroup('jobs')]
    #[SettingType(CronExpression::class)]
    public const EP_RECALCULATOR_ASSETS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>

    // <editor-fold desc="EP_RECALCULATOR_DOCUMENTS_RECALCULATOR">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(RecalculatorDocumentsRecalculator::class, 'enabled')]
    #[SettingGroup('jobs')]
    public const EP_RECALCULATOR_DOCUMENTS_RECALCULATOR_ENABLED = false;

    /**
     * Cron expression.
     */
    #[Service(RecalculatorDocumentsRecalculator::class, 'cron')]
    #[SettingGroup('jobs')]
    #[SettingType(CronExpression::class)]
    public const EP_RECALCULATOR_DOCUMENTS_RECALCULATOR_CRON = '0 0 1 * *';
    // </editor-fold>
    // </editor-fold>
}
