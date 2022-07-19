<?php declare(strict_types = 1);

namespace App\Rules;

use Config\Constants;

class Image extends File {
    protected function getMaxSize(): int {
        return $this->config->get('ep.image.max_size')
            ?? Constants::EP_IMAGE_MAX_SIZE;
    }

    /**
     * @return array<string>
     */
    protected function getMimeTypes(): array {
        return $this->config->get('ep.image.formats')
            ?? Constants::EP_IMAGE_FORMATS;
    }
}
