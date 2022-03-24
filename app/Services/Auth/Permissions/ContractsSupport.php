<?php declare(strict_types = 1);

namespace App\Services\Auth\Permissions;

use App\Services\Auth\Permission;

final class ContractsSupport extends Permission {
    public function __construct() {
        parent::__construct('contracts-support');
    }
}
