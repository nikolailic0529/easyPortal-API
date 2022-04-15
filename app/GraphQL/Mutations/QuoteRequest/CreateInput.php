<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\QuoteRequest;

use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectArray;
use Illuminate\Http\UploadedFile;

class CreateInput extends JsonObject {
    public ?string $oem_id          = null;
    public ?string $oem_custom      = null;
    public ?string $type_id         = null;
    public ?string $type_custom     = null;
    public ?string $message         = null;
    public ?string $customer_id     = null;
    public ?string $customer_custom = null;
    public string  $contact_name;
    public string  $contact_phone;
    public string  $contact_email;

    /**
     * @var array<UploadedFile>|null
     */
    #[JsonObjectArray(UploadedFile::class)]
    public ?array $files = null;

    /**
     * @var array<CreateAssetInput>|null
     */
    #[JsonObjectArray(CreateAssetInput::class)]
    public ?array $assets = null;

    /**
     * @var array<CreateDocumentInput>|null
     */
    #[JsonObjectArray(CreateDocumentInput::class)]
    public ?array $documents = null;
}
