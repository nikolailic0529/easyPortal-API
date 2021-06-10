<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use SplFileInfo;

class UpdateCompanyFile extends Input {
    public string       $companyId;
    public ?SplFileInfo $file;
}
