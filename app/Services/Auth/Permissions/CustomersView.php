<?php declare(strict_types = 1);

namespace App\Services\Auth\Permissions;

use App\Services\Auth\Contracts\Permissions\Composite;
use App\Services\Auth\Permission;

final class CustomersView extends Permission implements Composite {
    public function __construct() {
        parent::__construct('customers-view');
    }

    /**
     * @inheritDoc
     */
    public function getPermissions(): array {
        return [
            new AssetsView(),
            new QuotesView(),
            new ContractsView(),
        ];
    }
}
