<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\ImporterState;
use App\Utils\Console\WithBooleanOptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

use function str_pad;
use function strtr;

use const STR_PAD_LEFT;

abstract class Import extends Command {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = '${command}
        {--u|update : Update ${objects} if exists (default)}
        {--U|no-update : Do not update ${objects} if exists}
        {--offset= : start processing from given offset}
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
        $from   = $this->option('from');
        $chunk  = $this->option('chunk');
        $limit  = $this->option('limit');
        $update = $this->getBooleanOption('update', true);
        $offset = $this->option('offset');

        if ($from || $chunk || $limit || $offset) {
            $this->line('Settings:');
        }

        if ($offset) {
            $this->line("    Offset:  {$offset}");
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

        if ($from || $chunk || $limit || $offset) {
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
        $chunkNumber = 0;
        $chunkLength = 10;
        $valueLength = 14;
        $previous    = new ImporterState();

        $importer
            ->setFrom($from)
            ->setUpdate($update)
            ->setLimit($limit)
            ->setOffset($offset)
            ->setChunkSize($chunk)
            ->onChange(
                function (ImporterState $state) use (&$previous, &$chunkNumber, $chunkLength, $valueLength): void {
                    $chunk        = $this->pad($chunkNumber, $chunkLength, '0');
                    $chunkEmpty   = $this->pad('', $chunkLength + 2, '-');
                    $chunkFailed  = $this->pad($state->failed - $previous->failed, $valueLength);
                    $chunkCreated = $this->pad($state->created - $previous->created, $valueLength);
                    $chunkUpdated = $this->pad($state->updated - $previous->updated, $valueLength);
                    $processed    = $this->pad($state->processed, $valueLength);
                    $offset       = $this->pad(" {$state->offset} ", $valueLength * 4 + 4 * 2 + 3, '-');
                    $lineOne      = "| {$chunk} | {$processed} | {$chunkCreated} | {$chunkUpdated} | {$chunkFailed} |";
                    $lineTwo      = "+{$chunkEmpty}+{$offset}+";

                    if ($state->failed - $previous->failed > 0) {
                        $this->warn($lineOne);
                    } elseif ($state->updated - $previous->updated > 0 || $state->created - $previous->created > 0) {
                        $this->info($lineOne);
                    } else {
                        $this->line($lineOne);
                    }

                    $this->line($lineTwo);

                    $previous    = clone $state;
                    $chunkNumber = $chunkNumber + 1;
                },
            )
            ->onFinish(function (ImporterState $state) use ($valueLength): void {
                $this->newLine();

                $processed = $this->pad($state->processed, $valueLength);
                $created   = $this->pad($state->created, $valueLength);
                $updated   = $this->pad($state->updated, $valueLength);
                $failed    = $this->pad($state->failed, $valueLength);

                $this->line("Processed: {$processed}");
                $this->line("Created:   {$created}");
                $this->line("Updated:   {$updated}");

                if ($state->failed) {
                    $this->warn("Failed:    {$failed}");
                } else {
                    $this->line("Failed:    {$failed}");
                }

                $this->newLine();
            })
            ->start();

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }

    protected function pad(mixed $value, int $length, string $pad = ' '): string {
        return str_pad((string) $value, $length, $pad, STR_PAD_LEFT);
    }
}
