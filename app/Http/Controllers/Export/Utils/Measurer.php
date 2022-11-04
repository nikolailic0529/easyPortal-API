<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use Illuminate\Support\Str;

use function mb_strlen;
use function str_replace;

class Measurer {
    /**
     * @var array<int<0, max>, int<1, max>>
     */
    private array $columns = [];

    public function __construct() {
        // empty
    }

    /**
     * @return array<int<0, max>, int>
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * @param array<int<0, max>, scalar|null> $columns
     */
    public function measure(array $columns): static {
        foreach ($columns as $index => $content) {
            $previous = $this->columns[$index] ?? null;
            $length   = $this->getLength($content);

            if ($length > 0 && $previous < $length) {
                $this->columns[$index] = $length;
            }
        }

        return $this;
    }

    /**
     * @return int<0, max>
     */
    protected function getLength(string|float|int|bool|null $content): int {
        $content = str_replace(["\r\n", "\n\r", "\r"], "\n", (string) $content);
        $content = Str::before($content, "\n");
        $length  = mb_strlen($content);

        return $length;
    }
}
