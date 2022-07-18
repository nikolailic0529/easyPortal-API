<?php declare(strict_types = 1);

namespace App\GraphQL\Objects;

use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectArray;
use Illuminate\Http\UploadedFile;

class MessageInput extends JsonObject {
    public string $subject;
    public string $message;

    /**
     * @var array<string>|null
     */
    public ?array $cc = null;

    /**
     * @var array<string>|null
     */
    public ?array $bcc = null;

    /**
     * @var array<UploadedFile>
     */
    #[JsonObjectArray(UploadedFile::class)]
    public array $files = [];
}
