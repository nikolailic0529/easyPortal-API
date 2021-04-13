<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Disc;
use App\Services\Filesystem;

use function array_push;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

class Translations {
    public function __construct(
        protected Filesystem $filesystem,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        $disc         = $this->filesystem->disk($this->getDisc());
        $translations = [];
        $file         = $this->getFile($args['locale']);
        if ($disc->exists($file)) {
            $translations = json_decode($disc->get($file), true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TranslationsFileCorrupted();
        }

        $output = [];
        foreach ($translations as $key => $value) {
            array_push($output, [
                'key'   => $key,
                'value' => $value,
            ]);
        }
        return $output;
    }

    public function getDisc(): Disc {
        return Disc::resources();
    }

    public function getFile(string $locale): string {
        return "{$locale}.json";
    }
}
