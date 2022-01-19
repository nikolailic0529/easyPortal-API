<?php declare(strict_types = 1);

namespace App\Services\Auth\Permissions;

use App\Services\Auth\Permission;
use App\Services\Auth\Permissions\Markers\IsOrgAdmin;

final class AssetsDownload extends Permission implements IsOrgAdmin {
    public function __construct() {
        parent::__construct('assets-download');
    }
}
