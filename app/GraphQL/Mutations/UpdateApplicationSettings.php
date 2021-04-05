<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Illuminate\Contracts\Filesystem\Factory;

use function json_decode;
use function json_encode;

class UpdateApplicationSettings {
    public function __construct(
        protected Factory $storage,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $path      = 'app/settings.json';
        $input     = $args['input'];
        $localDisk = $this->storage->disk('local');
        $settings  = [];

        // Check if settings json file exists
        if ($localDisk->exists($path)) {
            $settings = json_decode($localDisk->get($path), true);
        }

        // update or create a setting
        foreach ($input as $setting) {
            $settings[$setting['name']] = $setting['value'];
        }
        $localDisk->put($path, json_encode($settings));
        return [ 'settings' => $input ];
    }
}
