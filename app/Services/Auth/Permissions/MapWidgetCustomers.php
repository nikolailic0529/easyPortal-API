<?php declare(strict_types = 1);

namespace App\Services\Auth\Permissions;

use App\Services\Auth\Permission;

class MapWidgetCustomers extends Permission {
    public function __construct() {
        parent::__construct('map-widget-customers');
    }
}
