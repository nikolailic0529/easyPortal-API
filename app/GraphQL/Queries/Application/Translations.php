<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\I18n\TranslationLoader;
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
        $translations = $this->translations->load($args['locale'], '*', '*');
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
