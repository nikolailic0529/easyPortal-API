<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Inputs;

use App\Services\DataLoader\Schema\Input;
use SplFileInfo;

class UpdateCompanyFile extends Input {
    public string       $companyId;
    public ?SplFileInfo $file;
}
