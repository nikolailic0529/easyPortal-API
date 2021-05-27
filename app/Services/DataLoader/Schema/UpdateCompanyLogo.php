<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use SplFileInfo;

class UpdateCompanyLogo extends Input {
    public string      $companyId;
    public SplFileInfo $logo;
}
