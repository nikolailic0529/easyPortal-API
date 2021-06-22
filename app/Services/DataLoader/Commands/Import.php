<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\Importers\Importer;
use App\Services\DataLoader\Importers\Status;
use App\Services\DataLoader\Schema\TypeWithId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

use function end;
use function filter_var;
use function reset;
use function str_pad;
use function strtr;

use const FILTER_VALIDATE_INT;
use const STR_PAD_LEFT;

abstract class Import extends Command {
    use GlobalScopes;
    use WithBooleanOptions;

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

    public function __construct() {
        $replacements      = $this->getReplacements();
        $this->signature   = strtr($this->signature, $replacements);
        $this->description = strtr($this->description, $replacements);

        parent::__construct();
    }


    /**
     * @return array<string,string>
     */
    abstract protected function getReplacements(): array;

    protected function process(Importer $importer): int {
        // Settings
        $from     = $this->option('from');
        $chunk    = $this->option('chunk');
        $limit    = $this->option('limit');
        $update   = $this->getBooleanOption('update', false);
        $continue = $this->option('continue');

        if ($from || $chunk || $limit || $continue) {
            $this->line('Settings:');
        }

        if ($from) {
            $from = Date::make($from);

            $this->line("    From:      {$from->toISOString()}");
        }

        if ($chunk) {
            $chunk = (int) $chunk;
            $this->line("    Chunk:     {$chunk}");
        }

        if ($limit) {
            $limit = (int) $limit;
            $this->line("    Limit:     {$limit}");
        }

        if ($continue) {
            if (filter_var($continue, FILTER_VALIDATE_INT)) {
                $continue = (int) $continue;
            }

            $this->line("    Continue:  {$continue}");
        }

        if ($from || $chunk || $limit || $continue) {
            $this->newLine();
        }

        // Begin
        $this->line(
            'Chunk #   : From                                 ... To                                   -  Processed',
        );
        $this->line(
            '            C:    created;        U:    updated;     F:     failed;',
        );

        // Process
        $length   = 10;
        $previous = new Status();

        $importer
            ->onChange(function (array $items, Status $status) use (&$previous, $length): void {
                $chunk        = str_pad((string) $status->chunk, $length, '0', STR_PAD_LEFT);
                $chunkFailed  = str_pad((string) ($status->failed - $previous->failed), $length, ' ', STR_PAD_LEFT);
                $chunkCreated = str_pad((string) ($status->created - $previous->created), $length, ' ', STR_PAD_LEFT);
                $chunkUpdated = str_pad((string) ($status->updated - $previous->updated), $length, ' ', STR_PAD_LEFT);
                $processed    = str_pad((string) $status->processed, $length, ' ', STR_PAD_LEFT);
                $first        = reset($items);
                $first        = str_pad($first instanceof TypeWithId ? $first->id : 'null', 36, ' ', STR_PAD_LEFT);
                $last         = end($items);
                $last         = str_pad($last instanceof TypeWithId ? $last->id : 'null', 36, ' ', STR_PAD_LEFT);
                $lineOne      = "{$chunk}: {$first} ... {$last} - {$processed}";
                $lineTwo      = "            C: {$chunkCreated};        U: {$chunkUpdated};     F: {$chunkFailed};";

                if ($status->failed - $previous->failed > 0) {
                    $this->warn($lineOne);
                    $this->warn($lineTwo);
                } else {
                    $this->info($lineOne);
                    $this->info($lineTwo);
                }

                $previous = clone $status;
            })
            ->onFinish(function (Status $status) use ($length): void {
                $this->newLine();

                $processed = str_pad((string) $status->processed, $length, ' ', STR_PAD_LEFT);
                $created   = str_pad((string) $status->created, $length, ' ', STR_PAD_LEFT);
                $updated   = str_pad((string) $status->updated, $length, ' ', STR_PAD_LEFT);
                $failed    = str_pad((string) $status->failed, $length, ' ', STR_PAD_LEFT);

                $this->line("Processed: {$processed}");
                $this->line("Created:   {$created}");
                $this->line("Updated:   {$updated}");

                if ($status->failed) {
                    $this->warn("Failed:    {$failed}");
                } else {
                    $this->line("Failed:    {$failed}");
                }

                $this->newLine();
            })
            ->import($update, $from, $continue, $chunk, $limit);

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
