<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Services\Search\Processors\FulltextsProcessor;
use App\Utils\Processor\Commands\ProcessorCommand;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

use function array_diff;
use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function implode;
use function is_a;
use function sort;
use function str_contains;

/**
 * @extends ProcessorCommand<FulltextsProcessor>
 */
class FulltextIndexesRebuild extends ProcessorCommand {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = 'Rebuild the FULLTEXT indexes for models.';

    /**
     * @inheritDoc
     */
    protected static function getCommandSignature(array $signature): array {
        return (new Collection(array_merge(parent::getCommandSignature($signature), [
            '{model?* : Model(s) to rebuild (default "all")}',
        ])))
            ->filter(static function (string $option): bool {
                return !str_contains($option, 'id?*');
            })
            ->all();
    }

    public function __invoke(FulltextsProcessor $processor): int {
        // Models
        $models  = array_values(array_unique((array) $this->argument('model')) ?: Relation::$morphMap);
        $valid   = array_filter($models, static function (string $model): bool {
            return class_exists($model)
                && is_a($model, Model::class, true);
        });
        $invalid = array_diff($models, $valid);

        if (count($models) === 0) {
            $this->warn('Nothing to rebuild.');

            return Command::SUCCESS;
        }

        if (count($invalid) > 0) {
            $this->warn('Following models are invalid/unknown: `'.implode('`, `', $invalid).'`.');

            return Command::FAILURE;
        }

        sort($valid);

        // Return
        return $this->process($processor->setModels($valid));
    }
}
