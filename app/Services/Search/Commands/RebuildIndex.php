<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Services\Search\Service;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

use function array_map;
use function array_unique;
use function max;
use function str_pad;

class RebuildIndex extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:search-rebuild-index
        {models?* : models to rebuild (default all)}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Rebuild the search index for the given model(s).';

    public function __invoke(ExceptionHandler $handler, Container $container, Service $service): int {
        $result = true;
        $models = array_unique((array) $this->argument('models')) ?: $service->getSearchableModels();
        $length = max(array_map('mb_strlen', $models));

        foreach ($models as $model) {
            $this->output->write(str_pad($model, $length).' ... ');

            try {
                $job = $service->getSearchableModelJob($model);

                if ($job) {
                    $container->make($job)->dispatch();

                    $this->info('OK');
                } else {
                    $this->warn('Model is not searchable.');

                    $result = false;
                }
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());
                $handler->report($exception);

                $result = false;
            }
        }

        // Done
        if ($result) {
            if ($models) {
                $this->newLine();
            }

            $this->info('Done.');
        }

        // Return
        return $result
            ? self::SUCCESS
            : self::FAILURE;
    }
}
