<?php declare(strict_types = 1);

namespace App\Rules;

class Image extends File {
    protected function getMaxSize(): int {
        return $this->config->get('ep.image.max_size');
    }

    /**
     * @return array<string>
     */
    protected function getMimeTypes(): array {
        return $this->config->get('ep.image.formats');
    }
}
