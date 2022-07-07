<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\Utils\JsonObject\JsonObject;
use Illuminate\Http\UploadedFile;

class ImportInput extends JsonObject {
    public UploadedFile $translations;
}
