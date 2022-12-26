<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Inputs;

use App\Services\DataLoader\Schema\Input;

class InputTranslationText extends Input {
    public string $language_code;
    public string $text;
}
