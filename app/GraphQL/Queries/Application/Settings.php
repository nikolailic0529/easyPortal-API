<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

class Settings {
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string,mixed>
     */
    public function __invoke($_, array $args): array {
        return [
            ['name' => 'ValueA', 'value' => 123],
            ['name' => 'ValueB', 'value' => 'asd'],
        ];
    }
}
