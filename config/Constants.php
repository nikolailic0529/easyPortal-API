<?php declare(strict_types = 1);

namespace Config;

use App\Jobs\Queues;
use App\Services\DataLoader\Jobs\CustomersUpdaterCronJob;
use App\Services\DataLoader\Jobs\CustomerUpdate;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellerUpdate;
use App\Services\Settings\Attributes\Group;
use App\Services\Settings\Attributes\Internal;
use App\Services\Settings\Attributes\Job;
use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Service;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Attributes\Type;
use App\Services\Settings\Jobs\ConfigUpdate;
use App\Services\Settings\Types\CronExpression;
use App\Services\Settings\Types\DocumentType;
use App\Services\Settings\Types\Duration;
use App\Services\Settings\Types\LocationType;
use App\Services\Settings\Types\OrganizationIdType;
use App\Services\Settings\Types\StringType;
use App\Services\Settings\Types\Text;
use App\Services\Settings\Types\Url;

use function interface_exists;

/**
 * This file should be loaded only once.
 *
 * @phpcs:disable PSR1.Files.SideEffects
 */
if (interface_exists(Constants::class)) {
    return;
}

/**
 * A list of application settings.
 *
 * Settings priorities:
 * - .env (used only if the application configuration is NOT cached)
 * - this list
 * - other configuration files
 */
interface Constants {
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
     */
    #[Setting('ep.contract_types')]
    #[Group('ep')]
    #[Type(DocumentType::class)]
    public const EP_CONTRACT_TYPES = [];

    /**
     * Types IDs related to quotes. Optional, if empty will use IDs which are
     * not in {@link \Config\Constants::EP_CONTRACT_TYPES}.
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
    #[Type(OrganizationIdType::class)]
    public const EP_ROOT_ORGANIZATION = '40765bbb-4736-4d2f-8964-1c3fd4e59aac';
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
     * Client Secret.
     */
    #[Setting('ep.keycloak.client_secret')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_CLIENT_SECRET = null;

    /**
     * The URI (can be relative) where user should be redirected after Sign In.
     */
    #[Setting('ep.keycloak.redirects.signin_uri')]
    #[Group('keycloak')]
    #[Type(StringType::class)]
    public const EP_KEYCLOAK_REDIRECTS_SIGNIN_URI = 'auth/organizations';

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
    // </editor-fold>

    // <editor-fold desc="EP_SETTINGS">
    // =========================================================================
    /**
     * Determines if the application should continue to work when the custom
     * config corrupted.
     */
    #[Setting('ep.settings.recoverable')]
    #[Group('ep')]
    #[Internal]
    public const EP_SETTINGS_RECOVERABLE = true;

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

    // <editor-fold desc="DATA_LOADER">
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
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CLIENT_SECRET = '';

    /**
     * Default chunk size.
     */
    #[Setting('ep.data_loader.chunk')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CHUNK = 100;

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

    // <editor-fold desc="DATA_LOADER_RESELLERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersImporterCronJob::class, 'enabled')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_ENABLED = self::EP_DATA_LOADER_ENABLED;

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
    public const EP_DATA_LOADER_RESELLERS_IMPORTER_QUEUE = Queues::DATA_LOADER_DEFAULT;
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_RESELLERS_UPDATER">
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
    public const EP_DATA_LOADER_RESELLERS_UPDATER_CRON = '*/5 * * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_QUEUE = Queues::DATA_LOADER_DEFAULT;

    /**
     * Expiration interval.
     */
    #[Service(ResellersUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_RESELLERS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_RESELLER_UPDATE">
    // -------------------------------------------------------------------------
    /**
     * Queue name.
     */
    #[Job(ResellerUpdate::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_RESELLER_UPDATE_QUEUE = Queues::DATA_LOADER_UPDATE;
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_CUSTOMERS_UPDATER">
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
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_CRON = '*/5 * * * *';

    /**
     * Queue name.
     */
    #[Service(CustomersUpdaterCronJob::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_QUEUE = Queues::DATA_LOADER_DEFAULT;

    /**
     * Expiration interval.
     */
    #[Service(CustomersUpdaterCronJob::class, 'settings.expire')]
    #[Group('data_loader')]
    #[Type(Duration::class)]
    public const EP_DATA_LOADER_CUSTOMERS_UPDATER_EXPIRE = 'PT24H';
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_CUSTOMER_UPDATE">
    // -------------------------------------------------------------------------
    /**
     * Queue name.
     */
    #[Job(CustomerUpdate::class, 'queue')]
    #[Group('data_loader')]
    public const EP_DATA_LOADER_CUSTOMER_UPDATE_QUEUE = Queues::DATA_LOADER_UPDATE;
    // </editor-fold>
    // </editor-fold>
}
