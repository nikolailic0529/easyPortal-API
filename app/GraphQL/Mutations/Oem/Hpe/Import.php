<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Oem\Hpe;

use App\Services\DataLoader\Importer\Importers\OemsImporter;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;

class Import {
    public function __construct(
        protected OemsImporter $importer,
    ) {
        // empty
    }

    /**
     * @param array{input: array{file: UploadedFile}} $args
     */
    public function __invoke(mixed $root, array $args): bool {
        try {
            $this->importer->import($args['input']['file']);
        } catch (LaravelExcelException $exception) {
            throw new ImportImportFailed($exception);
        }

        return true;
    }
}
