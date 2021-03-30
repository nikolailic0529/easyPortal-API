<?php declare(strict_types = 1);

use App\Setting;

/**
 * This file contains a list of application settings
 */

return [
    'contract_types' => Setting::getArray('EASYPORTAL_CONTRACT_TYPES'),
    'quote_types'    => Setting::getArray('EASYPORTAL_QUOTE_TYPES'),
];
