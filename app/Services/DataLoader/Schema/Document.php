<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class Document extends Type {
    public string                      $id;
    public string                      $type;
    public string                      $documentId;
    public ?string                     $resellerId;
    public ?string                     $customerId;
    public string                      $startDate;
    public string                      $endDate;
    public DocumentVendorSpecificField $vendorSpecificFields;
}
