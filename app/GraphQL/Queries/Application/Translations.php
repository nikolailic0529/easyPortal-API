<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\TranslationLoader;
use Illuminate\Contracts\Foundation\Application;

class Translations {
    public function __construct(
        protected Application $app,
        protected TranslationLoader $translations,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        $translations = $this->translations->load($this->app->getLocale(), '*', '*');
        $output       = [];

        foreach ($translations as $key => $value) {
            $output[] = [
                'key'   => $key,
                'value' => $value,
            ];
        }

        return $output;
    }
}
