<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Schema\Type;

class TranslationText extends Type {
    public string $language_code;
    public string $text;
}
