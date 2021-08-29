<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Importer\Status;
use App\Services\KeyCloak\Importer\UsersImporter;
use Illuminate\Console\Command;

use function app;
use function min;
use function strtr;

class SyncUsers extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = '${command}
        {--continue= : continue processing from given ${object}}
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
        protected Client $client,
        protected UsersImporter $importer,
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
        // Dependencies

        $importer = app()->make(UsersImporter::class);

        // param
        $continue = $this->option('continue');
        $chunk    = (int) $this->option('chunk');
        $limit    = (int) $this->option('limit');
        $total    = 0;
        $bar      = null;
        $importer
            ->onInit(function () use (&$bar, &$total, $limit): void {
                $client = app()->make(Client::class);
                $total  = $client->usersCount();
                if ($limit > 0) {
                    $total = min($total, $limit);
                }

                $bar = $this->output->createProgressBar($total);
            })
            ->onChange(static function (Status $status, int $offset) use (&$bar): void {
                $bar->setProgress($status->processed);
            })
            ->import($continue, $chunk, $limit, $total);

        $bar->finish();
    }
}
