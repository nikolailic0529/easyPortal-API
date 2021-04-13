<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Disc;
use App\Services\Filesystem;
use Exception;

use function array_values;
use function json_decode;
use function json_encode;

class UpdateApplicationTranslations {
    public function __construct(
        protected Filesystem $filesystem,
    ) {
        // empty
    }

    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $inputTranslations = $args['input']['translations'];
        $disc              = $this->filesystem->disk($this->getDisc());
        $file              = $this->getFile($args['input']['locale']);
        $translations      = [];

        // Check if translation json file exists
        if ($disc->exists($file)) {
            $translations = json_decode($disc->get($file), true) ?: [];
        }

        $updated = [];
        foreach ($inputTranslations as $translation) {
            $translations[$translation['key']] = $translation['value'];
            $updated[$translation['key']]      = $translation;
        }

        $error   = null;
        $success = false;

        try {
            $success = $disc->put($file, json_encode($translations));
        } catch (Exception $exception) {
            $error = $exception;
        }

        if (!$success) {
            throw new UpdateApplicationTranslationsFailedToSave($error);
        }

        return [ 'translations' => array_values($updated) ];
    }

    public function getDisc(): Disc {
        return Disc::app();
    }

    public function getFile(string $locale): string {
        return "lang/{$locale}.json";
    }
}
