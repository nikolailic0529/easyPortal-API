<?php declare(strict_types = 1);

use App\Setting;

/**
 * This file contains a list of application settings
 */

return [
    'contracts_type_ids' => Setting::get('CONTRACTS_TYPE_IDS'),
    'quotes_type_ids'    => Setting::get('QUOTES_TYPE_IDS'),
];
