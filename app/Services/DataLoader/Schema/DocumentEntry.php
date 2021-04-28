<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class DocumentEntry extends Type {
    public string|null $currencyCode;
    public string|null $netPrice;
    public string|null $discount;
    public string|null $listPrice;
}
