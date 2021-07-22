<?php declare(strict_types = 1);

namespace App\Rules;

class Spreadsheet extends File {
    /**
     * @return array<string>
     */
    protected function getMimeTypes(): array {
        return ['xlsx'];
    }
}
