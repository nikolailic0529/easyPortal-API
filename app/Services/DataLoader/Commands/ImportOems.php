<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importers\OemsImporter;
use Illuminate\Console\Command;

class ImportOems extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-import-oems
        {file : The path to excel file with data}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Import oems, service groups and service levels.';

    public function __invoke(OemsImporter $importer): int {
        // Import
        $importer->import($this->argument('file'));

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
