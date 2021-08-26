<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Importer\Status;
use App\Services\KeyCloak\Importer\UserImporter;
use Illuminate\Console\Command;

use function strtr;
class SyncUsers extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = '${command}
        {--u|update : Update ${objects} if exists}
        {--U|no-update : Do not update ${objects} if exists (default)}
        {--continue= : continue processing from given ${object}}
        {--from= : start processing from given datetime}
        {--limit= : max ${objects} to process}
        {--chunk= : chunk size}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Import all ${objects}.';

    public function __construct(
        protected UsersIterator $iterator,
        protected Client $client,
        protected UserImporter $importer,
    ) {
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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void {
        $total = $this->client->usersCount();
        $bar   = $this->output->createProgressBar($total);
        $bar->start();

        $continue = $this->option('continue');
        $chunk    = (int) $this->option('chunk');
        $limit    = (int) $this->option('limit');

        $this->importer->onChange(static function (Status $status, int $offset) use ($bar): void {
            $bar->setProgress($status->processed);
        })
        ->import($continue, $chunk, $limit);
        $bar->finish();
    }
}
