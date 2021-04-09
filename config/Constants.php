<?php declare(strict_types = 1);

namespace Config;

use App\Jobs\Queues;
use App\Services\DataLoader\Jobs\LocationsCleanupCronJob;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellerUpdate;
use App\Services\Settings\Attributes\Group;
use App\Services\Settings\Attributes\Internal;
use App\Services\Settings\Attributes\Job;
use App\Services\Settings\Attributes\Service;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Attributes\Type;
use App\Services\Settings\Jobs\ConfigUpdate;
use App\Services\Settings\Types\CronExpression;
use App\Services\Settings\Types\Expiration;
use App\Services\Settings\Types\StringType;
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
    #[Setting('easyportal.image.max_size')]
    public const EASYPORTAL_IMAGE_MAX_SIZE = 2048;

    /**
     * Accepted image formats.
     */
    #[Setting('easyportal.image.formats')]
    #[Type(StringType::class)]
    public const EASYPORTAL_IMAGE_FORMATS = ['jpg', 'jpeg', 'png'];

    /**
     * Root user ID.
     */
    #[Setting('easyportal.root_user_id')]
    #[Internal]
    public const EASYPORTAL_ROOT_USER_ID = '';

    /**
     * Type IDs related to contracts.
     */
    #[Setting('easyportal.contract_types')]
    #[Type(StringType::class)]
    public const EASYPORTAL_CONTRACT_TYPES = [];

    /**
     * Types IDs related to quotes. Optional, if empty will use IDs which are
     * not in {@link \Config\Constants::EASYPORTAL_CONTRACT_TYPES}.
     */
    #[Setting('easyportal.quote_types')]
    #[Type(StringType::class)]
    public const EASYPORTAL_QUOTE_TYPES = [];
    // </editor-fold>

    // <editor-fold desc="EP_SETTINGS">
    // =========================================================================
    // <editor-fold desc="EP_SETTINGS_CONFIG_UPDATE">
    // -------------------------------------------------------------------------
    /**
     * Queue name.
     */
    #[Job(ConfigUpdate::class, 'queue')]
    public const EP_SETTINGS_CONFIG_UPDATE_QUEUE = Queues::DEFAULT;
    // </editor-fold>
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting('easyportal.data-loader.enabled')]
    #[Group('data_loader')]
    public const DATA_LOADER_ENABLED = true;

    /**
     * Default chunk size.
     */
    #[Setting('easyportal.data-loader.chunk')]
    #[Group('data_loader')]
    public const DATA_LOADER_CHUNK = 100;

    /**
     * GraphQL Endpoint.
     */
    #[Setting('easyportal.data-loader.endpoint')]
    #[Group('data_loader')]
    #[Type(Url::class)]
    public const DATA_LOADER_ENDPOINT = '';

    // <editor-fold desc="DATA_LOADER_RESELLERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersImporterCronJob::class, 'enabled')]
    public const DATA_LOADER_RESELLERS_IMPORTER_ENABLED = self::DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(ResellersImporterCronJob::class, 'cron')]
    #[Type(CronExpression::class)]
    public const DATA_LOADER_RESELLERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersImporterCronJob::class, 'queue')]
    public const DATA_LOADER_RESELLERS_IMPORTER_QUEUE = Queues::DATA_LOADER_DEFAULT;
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_RESELLERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(ResellersUpdaterCronJob::class, 'enabled')]
    public const DATA_LOADER_RESELLERS_UPDATER_ENABLED = self::DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(ResellersUpdaterCronJob::class, 'cron')]
    #[Type(CronExpression::class)]
    public const DATA_LOADER_RESELLERS_UPDATER_CRON = '*/5 * * * *';

    /**
     * Queue name.
     */
    #[Service(ResellersUpdaterCronJob::class, 'queue')]
    public const DATA_LOADER_RESELLERS_UPDATER_QUEUE = Queues::DATA_LOADER_DEFAULT;

    /**
     * Queue name.
     */
    #[Service(ResellersUpdaterCronJob::class, 'settings.expire')]
    #[Type(Expiration::class)]
    public const DATA_LOADER_RESELLERS_UPDATER_EXPIRE = '24 hours';
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_LOCATIONS_CLEANUP">
    // -------------------------------------------------------------------------
    /**
     * Enabled?
     */
    #[Service(LocationsCleanupCronJob::class, 'enabled')]
    public const DATA_LOADER_LOCATIONS_CLEANUP_ENABLED = self::DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[Service(LocationsCleanupCronJob::class, 'cron')]
    #[Type(CronExpression::class)]
    public const DATA_LOADER_LOCATIONS_CLEANUP_CRON = '0 */6 * * *';

    /**
     * Queue name.
     */
    #[Service(LocationsCleanupCronJob::class, 'queue')]
    public const DATA_LOADER_LOCATIONS_CLEANUP_QUEUE = Queues::DATA_LOADER_DEFAULT;
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_RESELLER_UPDATE">
    // -------------------------------------------------------------------------
    /**
     * Queue name.
     */
    #[Job(ResellerUpdate::class, 'queue')]
    public const DATA_LOADER_RESELLER_UPDATE_QUEUE = Queues::DATA_LOADER_RESELLER;
    // </editor-fold>
    // </editor-fold>
}
