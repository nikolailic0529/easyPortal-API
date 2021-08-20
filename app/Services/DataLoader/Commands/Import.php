<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\Importers\Importer;
use App\Services\DataLoader\Importers\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

use function str_pad;
use function strtr;

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

        if ($continue) {
            $this->line("    Continue:  {$continue}");
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

        if ($from || $chunk || $limit || $continue) {
            $this->newLine();
        }

        // Begin
        $this->line(
            '+------------+----------------+----------------+----------------+----------------+',
        );
        $this->line(
            '| Chunk      |      Processed |        Created |        Updated |         Failed |',
        );
        $this->line(
            '+============+================+================+================+================+',
        );

        // Process
        $chunkLength = 10;
        $valueLength = 14;
        $previous    = new Status();

        $importer
            ->onChange(function (array $items, Status $status) use (&$previous, $chunkLength, $valueLength): void {
                $chunk        = $this->pad($status->chunk, $chunkLength, '0');
                $chunkEmpty   = $this->pad('', $chunkLength + 2, '-');
                $chunkFailed  = $this->pad($status->failed - $previous->failed, $valueLength);
                $chunkCreated = $this->pad($status->created - $previous->created, $valueLength);
                $chunkUpdated = $this->pad($status->updated - $previous->updated, $valueLength);
                $processed    = $this->pad($status->processed, $valueLength);
                $continue     = $this->pad(" {$status->continue} ", $valueLength * 4 + 4 * 2 + 3, '-');
                $lineOne      = "| {$chunk} | {$processed} | {$chunkCreated} | {$chunkUpdated} | {$chunkFailed} |";
                $lineTwo      = "+{$chunkEmpty}+{$continue}+";

                if ($status->failed - $previous->failed > 0) {
                    $this->warn($lineOne);
                } elseif ($status->updated - $previous->updated > 0 || $status->created - $previous->created > 0) {
                    $this->info($lineOne);
                } else {
                    $this->line($lineOne);
                }

                $this->line($lineTwo);

                $previous = clone $status;
            })
            ->onFinish(function (Status $status) use ($valueLength): void {
                $this->newLine();

                $processed = $this->pad($status->processed, $valueLength);
                $created   = $this->pad($status->created, $valueLength);
                $updated   = $this->pad($status->updated, $valueLength);
                $failed    = $this->pad($status->failed, $valueLength);

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

    protected function pad(mixed $value, int $length, string $pad = ' '): string {
        return str_pad((string) $value, $length, $pad, STR_PAD_LEFT);
    }
}
