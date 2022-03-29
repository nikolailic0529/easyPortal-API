<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Organization;

final class WithOrganizationToken extends Organization {
    protected function __construct() {
        parent::__construct();
    }
}
