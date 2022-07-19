<?php declare(strict_types = 1);

namespace App\Rules;

use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\Factory;

use function implode;

class File extends CompositeRule {
    public function __construct(
        Factory $factory,
        protected Repository $config,
    ) {
        parent::__construct($factory);
    }

    /**
     * @inheritDoc
     */
    protected function getRules(): array {
        $size  = $this->getMaxSize();
        $types = implode(',', $this->getMimeTypes());
        $rules = [
            "max:{$size}",
            "mimes:{$types}",
        ];

        return $rules;
    }

    protected function getMaxSize(): int {
        return $this->config->get('ep.file.max_size')
            ?? Constants::EP_FILE_MAX_SIZE;
    }

    /**
     * @return array<string>
     */
    protected function getMimeTypes(): array {
        return $this->config->get('ep.file.formats')
            ?? Constants::EP_FILE_FORMATS;
    }
}
