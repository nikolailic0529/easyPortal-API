<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class TranslationText extends Type {
    public string $language_code;
    public string $text;
}
