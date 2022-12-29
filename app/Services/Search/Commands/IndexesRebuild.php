<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Services\Search\Processors\ModelsProcessor;
use App\Services\Search\Service;
use App\Utils\Processor\Commands\ProcessorCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;

use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function implode;
use function is_a;
use function sort;

/**
 * @extends ProcessorCommand<ModelsProcessor>
 */
class IndexesRebuild extends ProcessorCommand {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = 'Rebuild the search index for the given model(s).';

    /**
     * @inheritDoc
     */
    protected static function getCommandSignature(array $signature): array {
        return array_merge(parent::getCommandSignature($signature), [
            '{model?* : Model(s) to rebuild (default "all")}',
        ]);
    }

    public function __invoke(
        ExceptionHandler $handler,
        Service $service,
        ModelsProcessor $processor,
    ): int {
        // Models
        $models  = array_values(array_unique((array) $this->argument('model')) ?: $service->getSearchableModels());
        $invalid = array_filter($models, static function (string $model) use ($service): bool {
            return !class_exists($model)
                || !is_a($model, Model::class, true)
                || !$service->isSearchableModel($model);
        });

        if (count($models) === 0) {
            $this->warn('Nothing to rebuild.');

            return Command::SUCCESS;
        }

        if (count($invalid) > 0) {
            $this->warn('Following models are not searchable: `'.implode('`, `', $invalid).'`.');

            return Command::FAILURE;
        }

        sort($models);

        // Run
        return $this->process($processor->setModels($models));
    }
}
