<?php declare(strict_types = 1);

/**
 * You, probably, MUST not edit this file.
 *
 * @see \Config\Constants
 */

use Config\Constants;

return [
    'settings' => [
        'recoverable' => env('EP_SETTINGS_RECOVERABLE', Constants::EP_SETTINGS_RECOVERABLE),
    ],
];
