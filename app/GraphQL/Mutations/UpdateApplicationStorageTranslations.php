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

        $updated = [];
        $deleted = [];

        $translations = (new Collection($translations))->keyBy(static function ($translation): string {
            return $translation['key'];
        });
        foreach ($inputTranslations as $translation) {
            if ($translation['delete']) {
                if (!$translations->has($translation['key'])) {
                    // So it doesn't return false deleted
                    continue;
                }
                $deleted[$translation['key']] = $translation;
                $translations->forget($translation['key']);
            } else {
                $data = [
                    'key'   => $translation['key'],
                    'value' => $translation['value'],
                ];
                $translations->put($translation['key'], $data);
                $updated[$translation['key']] = $data;
            }
        }

        $error   = null;
        $success = false;

        try {
            $success = $disc->put($file, json_encode($translations->values()));
        } catch (Exception $exception) {
            $error = $exception;
        }

        if (!$success) {
            throw new UpdateApplicationStorageTranslationsFailedToSave($error);
        }

        return [
            'updated' => array_values($updated),
            'deleted' => array_values($deleted),
        ];
    }

    public function getDisc(): Disc {
        return Disc::ui();
    }

    public function getFile(string $locale): string {
        return "lang/{$locale}.json";
    }
}
