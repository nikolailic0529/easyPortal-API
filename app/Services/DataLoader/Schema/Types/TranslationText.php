<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class TranslationText extends Type {
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $language_code;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $text;
}
