<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\DataLoader\Importer\Importers\OemsImporter;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;

class ImportOems {
    public function __construct(
        protected OemsImporter $importer,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $file = $args['input']['file'] ?? null;

        if (!($file instanceof UploadedFile)) {
            return [
                'result' => false,
            ];
        }

        try {
            $this->importer->import($file);
        } catch (LaravelExcelException $exception) {
            throw new ImportOemsImportFailed($exception);
        }

        return [
            'result' => true,
        ];
    }
}
