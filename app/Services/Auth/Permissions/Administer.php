<?php declare(strict_types = 1);

namespace App\Services\Auth\Permissions;

use App\Services\Auth\Permission;
use App\Services\Auth\Permissions\Markers\IsRoot;

final class Administer extends Permission implements IsRoot {
    public function __construct() {
        parent::__construct('administer');
    }
}
