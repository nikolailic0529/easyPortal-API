<?php declare(strict_types = 1);

namespace App\Services\I18n\Storages;

use DateInterval;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Reader\XLSX\Reader;
use SplFileInfo;

use function is_bool;
use function is_float;
use function str_replace;
use function trim;

class Spreadsheet {
    public function __construct(
        protected SplFileInfo $file,
    ) {
        // empty
    }

    /**
     * @return array<string, string|null>
     */
    public function load(): array {
        $reader       = new Reader();
        $translations = [];

        try {
            $reader->open($this->file->getPathname());

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    // Header?
                    if ($index <= 1) {
                        continue;
                    }

                    // Extract
                    $cells = $row->getCells();
                    $key   = $this->getCellValue($cells[0] ?? null);

                    if ($key) {
                        $translations[$key] = $this->getCellValue($cells[1] ?? null);
                    }
                }
            }
        } finally {
            $reader->close();
        }

        return $translations;
    }

    protected function getCellValue(?Cell $cell): ?string {
        // Null?
        if ($cell === null) {
            return null;
        }

        // Value (type support is limited, because I have no idea what format should be used...)
        $value = $cell->getValue();

        if ($value === null || $value instanceof DateTimeInterface || $value instanceof DateInterval) {
            $value = null;
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_float($value)) {
            $value = str_replace(',', '.', (string) $value);
        } else {
            $value = trim((string) $value);
            $value = str_replace(["\r\n", "\n\r", "\r"], "\n", $value);
        }

        return $value;
    }
}
