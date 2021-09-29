<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Jobs\Recalculate as RecalculateJob;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;

use function array_unique;
use function count;
use function strtr;

abstract class Recalculate extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = '${command}
        {id?* : The ID of the ${objects}}
        {--chunk= : chunk size}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Recalculate all ${objects}.';

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

    protected function process(Repository $config, RecalculateJob $job): int {
        // Prepare
        $keys  = array_unique($this->argument('id'));
        $chunk = ((int) $this->option('chunk')) ?: $config->get('ep.data_loader.chunk');
        $model = $job->getModel();
        $query = $model::query();

        if ($keys) {
            $query = $query->whereIn($model->getKeyName(), $keys);
        }

        $this->info(strtr('Recalculating ${objects}:', $this->getReplacements()));

        // Run
        GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            function () use ($job, $query, $chunk): void {
                $progress = $this->output->createProgressBar($query->count());

                $progress->display();

                $query->chunk($chunk, static function (Collection $items) use ($job, $progress): void {
                    (clone $job)->setModels($items)->run();

                    $progress->advance(count($items));
                });

                $progress->finish();
            },
        );

        // Done
        $this->newLine(2);
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
