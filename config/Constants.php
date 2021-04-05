<?php declare(strict_types = 1);

namespace Config;

use App\Jobs\Queues;
use App\Services\DataLoader\Jobs\LocationsCleanupCronJob;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellerUpdate;
use App\Services\Settings\Attributes\CronJob;
use App\Services\Settings\Attributes\Job;
use App\Services\Settings\Attributes\Readonly;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Attributes\Type;
use App\Services\Settings\Types\CronExpression;
use App\Services\Settings\Types\Expiration;
use App\Services\Settings\Types\StringScalar;
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
    /**
     * Max size of branding images/icons (branding_favicon, branding_logo) in KB.
     */
    #[Setting('easyportal.max_image_size')]
    public const EASYPORTAL_MAX_IMAGE_SIZE = 2048;

    /**
     * Accepted image formats.
     */
    #[Setting('easyportal.image_formats')]
    #[Type(StringScalar::class)]
    public const EASYPORTAL_IMAGE_FORMATS = ['jpg', 'jpeg', 'png'];

    /**
     * Root user ID.
     */
    #[Name('easyportal.root_user_id')]
    #[Readonly]
    public const EASYPORTAL_ROOT_USER_ID = '';

    /**
     * Type IDs related to contracts.
     */
    #[Setting('easyportal.contract_types')]
    #[Type(StringScalar::class)]
    public const EASYPORTAL_CONTRACT_TYPES = [];

    /**
     * Types IDs related to quotes. Optional, if empty will use IDs which are
     * not in {@link \Config\Constants::EASYPORTAL_CONTRACT_TYPES}.
     */
    #[Setting('easyportal.quote_types')]
    #[Type(StringScalar::class)]
    public const EASYPORTAL_QUOTE_TYPES = [];

    // <editor-fold desc="DATA_LOADER">
    // =========================================================================
    /**
     * Enabled?
     */
    #[Setting('data-loader.enabled')]
    public const DATA_LOADER_ENABLED = true;

    /**
     * Default chunk size.
     */
    #[Setting('data-loader.chunk')]
    public const DATA_LOADER_CHUNK = 100;

    /**
     * GraphQL Endpoint.
     */
    #[Setting('data-loader.endpoint')]
    #[Type(Url::class)]
    public const DATA_LOADER_ENDPOINT = '';

    // <editor-fold desc="DATA_LOADER_RESELLERS_IMPORTER">
    // -------------------------------------------------------------------------
    /**
     * DO NOT EDIT.
     */
    #[CronJob(ResellersImporterCronJob::class, 'name')]
    #[Readonly]
    public const DATA_LOADER_RESELLERS_IMPORTER = 'data-loader-resellers-importer';

    /**
     * Enabled?
     */
    #[CronJob(ResellersImporterCronJob::class, 'enabled')]
    public const DATA_LOADER_RESELLERS_IMPORTER_ENABLED = self::DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[CronJob(ResellersImporterCronJob::class, 'cron')]
    #[Type(CronExpression::class)]
    public const DATA_LOADER_RESELLERS_IMPORTER_CRON = '0 0 * * *';

    /**
     * Queue name.
     */
    #[CronJob(ResellersImporterCronJob::class, 'queue')]
    public const DATA_LOADER_RESELLERS_IMPORTER_QUEUE = Queues::DATA_LOADER_DEFAULT;
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_RESELLERS_UPDATER">
    // -------------------------------------------------------------------------
    /**
     * DO NOT EDIT.
     */
    #[CronJob(ResellersUpdaterCronJob::class, 'name')]
    #[Readonly]
    public const DATA_LOADER_RESELLERS_UPDATER = 'data-loader-resellers-updater';

    /**
     * Enabled?
     */
    #[CronJob(ResellersUpdaterCronJob::class, 'enabled')]
    public const DATA_LOADER_RESELLERS_UPDATER_ENABLED = self::DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[CronJob(ResellersUpdaterCronJob::class, 'cron')]
    #[Type(CronExpression::class)]
    public const DATA_LOADER_RESELLERS_UPDATER_CRON = '*/5 * * * *';

    /**
     * Queue name.
     */
    #[CronJob(ResellersUpdaterCronJob::class, 'queue')]
    public const DATA_LOADER_RESELLERS_UPDATER_QUEUE = Queues::DATA_LOADER_DEFAULT;

    /**
     * Queue name.
     */
    #[CronJob(ResellersUpdaterCronJob::class, 'settings.expire')]
    #[Type(Expiration::class)]
    public const DATA_LOADER_RESELLERS_UPDATER_EXPIRE = '24 hours';
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_LOCATIONS_CLEANUP">
    // -------------------------------------------------------------------------
    /**
     * DO NOT EDIT.
     */
    #[CronJob(LocationsCleanupCronJob::class, 'name')]
    #[Readonly]
    public const DATA_LOADER_LOCATIONS_CLEANUP = 'data-loader-locations-cleanup';

    /**
     * Enabled?
     */
    #[CronJob(LocationsCleanupCronJob::class, 'enabled')]
    public const DATA_LOADER_LOCATIONS_CLEANUP_ENABLED = self::DATA_LOADER_ENABLED;

    /**
     * Cron expression.
     */
    #[CronJob(LocationsCleanupCronJob::class, 'cron')]
    #[Type(CronExpression::class)]
    public const DATA_LOADER_LOCATIONS_CLEANUP_CRON = '0 */6 * * *';

    /**
     * Queue name.
     */
    #[CronJob(LocationsCleanupCronJob::class, 'queue')]
    public const DATA_LOADER_LOCATIONS_CLEANUP_QUEUE = Queues::DATA_LOADER_DEFAULT;
    // </editor-fold>

    // <editor-fold desc="DATA_LOADER_RESELLER_UPDATE">
    // -------------------------------------------------------------------------
    /**
     * DO NOT EDIT.
     */
    #[Job(ResellerUpdate::class, 'name')]
    #[Readonly]
    public const DATA_LOADER_RESELLER_UPDATE = 'data-loader-reseller-update';

    /**
     * Queue name.
     */
    #[Job(ResellerUpdate::class, 'queue')]
    public const DATA_LOADER_RESELLER_UPDATE_QUEUE = Queues::DATA_LOADER_RESELLER;
    // </editor-fold>
    // </editor-fold>
}
