<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;

class ImportOems {
    public function __construct(
        protected Kernel $artisan,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $file = $args['input']['file'] ?? null;

        if (!($file instanceof UploadedFile)) {
            return [
                'result' => false,
            ];
        }

        return [
            'result' => $this->artisan->call('ep:data-loader-oems-import', [
                'file' => $file->getPathname(),
            ]) === Command::SUCCESS,
        ];
    }
}
