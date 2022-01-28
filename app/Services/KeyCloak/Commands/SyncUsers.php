<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Services\KeyCloak\Importer\UsersImporter;
use App\Utils\Processor\State;
use Illuminate\Console\Command;

use function strtr;

class SyncUsers extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = '${command}
        {--offset= : start processing from given ${object}}
        {--limit=  : max ${objects} to process}
        {--chunk=  : chunk size}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Import all ${objects}.';

    public function __construct() {
        $replacements      = $this->getReplacements();
        $this->signature   = strtr($this->signature, $replacements);
        $this->description = strtr($this->description, $replacements);

        parent::__construct();
    }

    /**
     * @return array<string, string>
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:keycloak-sync-users',
            '${objects}' => 'users',
            '${object}'  => 'user',
        ];
    }

    public function __invoke(UsersImporter $importer): int {
        // Import
        $offset = $this->option('offset');
        $chunk  = ((int) $this->option('chunk')) ?: null;
        $limit  = ((int) $this->option('limit')) ?: null;
        $bar    = $this->output->createProgressBar();

        $importer
            ->setChunkSize($chunk)
            ->setOffset($offset)
            ->setLimit($limit)
            ->onInit(static function (State $state) use ($bar): void {
                if ($state->total) {
                    $bar->setMaxSteps($state->total);
                }
            })
            ->onChange(static function (State $state) use ($bar): void {
                $bar->setProgress($state->processed);
            })
            ->start();

        $bar->finish();

        // Done
        $this->newLine(2);
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
