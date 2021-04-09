<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Disc;
use App\Services\Filesystem;
use Exception;
use Illuminate\Support\Collection;

use function array_values;
use function json_decode;
use function json_encode;

class UpdateApplicationStorageTranslations {
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

        $updated      = [];
        $translations = (new Collection($translations))->keyBy(static function ($translation): string {
            return $translation['key'];
        });
        foreach ($inputTranslations as $translation) {
            $translations->put($translation['key'], $translation);
            $updated[$translation['key']] = $translation;
        }

        $error   = null;
        $success = false;

        try {
            $success = $disc->put($file, json_encode($translations->values()));
        } catch (Exception $exception) {
            $error = $exception;
        }

        if (!$success) {
            throw new StorageTranslationsFailedToSave($error);
        }

        return [ 'translations' => array_values($updated) ];
    }

    public function getDisc(): Disc {
        return Disc::ui();
    }

    public function getFile(string $locale): string {
        return "lang/{$locale}.json";
    }
}
