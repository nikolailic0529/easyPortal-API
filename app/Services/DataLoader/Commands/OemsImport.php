<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Importer\Importers\OemsImporter;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'ep:data-loader-oems-import')]
class OemsImport extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-oems-import
        {file : The path to excel file with data}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
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
