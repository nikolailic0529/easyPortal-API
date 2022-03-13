<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class InputTranslationText extends Input {
    public string $language_code;
    public string $text;
}
