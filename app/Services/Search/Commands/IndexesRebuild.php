<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Services\I18n\Formatter;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Service;
use App\Utils\Processor\Commands\ProcessorCommand;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Throwable;

use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function str_contains;

class IndexesRebuild extends ProcessorCommand {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Rebuild the search index for the given model(s).';

    /**
     * @inheritDoc
     */
    protected function getCommandSignature(array $signature): array {
        return (new Collection(array_merge(parent::getCommandSignature($signature), [
            '{model?* : model(s) to rebuild (default all)}',
        ])))
            ->filter(static function (string $option): bool {
                return !str_contains($option, '--offset=')
                    && !str_contains($option, '--limit=')
                    && !str_contains($option, 'id?*');
            })
            ->all();
    }

    public function __invoke(
        ExceptionHandler $handler,
        Container $container,
        Service $service,
        Formatter $formatter,
    ): int {
        $result = 0;
        $models = array_values(array_unique((array) $this->argument('model')) ?: $service->getSearchableModels());

        for ($i = 0, $c = count($models); $i < $c; $i++) {
            $model = $models[$i];
            $break = $i < $c - 1;

            $this->output->writeln("Processing `<info>{$model}</info>`:");

            try {
                $processor = $container->make(Processor::class)->setModel($model)->setRebuild(true);
                $job       = $service->getSearchableModelJob($model);

                if ($job) {
                    $result += $this->process($formatter, $processor);
                } else {
                    $this->warn('    not searchable');

                    $result += 1;
                }
            } catch (Throwable $exception) {
                $this->error("    {$exception->getMessage()}");
                $handler->report($exception);

                $result += 1;
            } finally {
                if ($break) {
                    $this->newLine();
                }
            }
        }

        // Return
        return $result === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    protected function getProcessorClass(): string {
        return Processor::class;
    }
}
