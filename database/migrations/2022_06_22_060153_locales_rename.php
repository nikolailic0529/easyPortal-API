<?php declare(strict_types = 1);

use App\Services\I18n\Migrations\AppTranslationsRename;

return new class() extends AppTranslationsRename {
    /**
     * @inheritDoc
     */
    protected function getRenameMap(): array {
        return [
            'de_DE' => 'de',
            'en_GB' => 'en',
            'fr_FR' => 'fr',
            'it_IT' => 'it',
        ];
    }
};
