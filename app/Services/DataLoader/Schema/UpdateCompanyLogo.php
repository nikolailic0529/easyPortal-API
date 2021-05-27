<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Psr\Http\Message\StreamInterface;

class UpdateCompanyLogo extends Input {
    public string          $companyId;
    public StreamInterface $logo;
}
