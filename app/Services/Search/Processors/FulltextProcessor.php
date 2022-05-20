<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Services\Search\Exceptions\FailedToRebuildFulltextIndexes;
use App\Services\Search\Exceptions\ProcessorError;
use App\Services\Search\Processors\Concerns\WithModels;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Model;
use stdClass;
use Throwable;

use function array_merge;
use function count;
use function explode;
use function preg_match;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Rebuild FULLTEXT indexes for Model.
 *
 * The `doctrine/dbal` doesn't support invisible indexes and parser for these
 * reasons the class uses raw sql.
 *
 * @extends Processor<class-string<Model>, null, FulltextProcessorState>
 */
class FulltextProcessor extends Processor {
    /**
     * @use WithModels<Model>
     */
    use WithModels;

    // <editor-fold desc="Process">
    // =========================================================================
    protected function getTotal(State $state): ?int {
        return count($state->models);
    }

    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator(
            $this->getExceptionHandler(),
            $state->models,
        );
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        /** @var Model $model */
        $model   = new $item();
        $table   = $model->getTable();
        $indexes = $this->getFulltextIndexes($model);
        $queries = [];

        foreach ($indexes as $index) {
            $queries[] = "ALTER TABLE `{$table}` DROP INDEX `{$index->getName()}`;";
            $queries[] = "ALTER TABLE `{$table}` ADD {$index->getSql()}";
        }

        $this->execute($model, $queries);
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        $this->getExceptionHandler()->report(
            $item
                ? new FailedToRebuildFulltextIndexes($this, $item, $exception)
                : new ProcessorError($this, $exception),
        );
    }

    /**
     * @inheritdoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new FulltextProcessorState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'models' => $this->getModels(),
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    /**
     * @return array<FulltextIndex>
     */
    protected function getFulltextIndexes(Model $model): array {
        $indexes = [];
        $matches = [];
        $regexp  = '/FULLTEXT (?:KEY|INDEX) `([^`]+)`/ui';
        $lines   = explode("\n", str_replace(["\r\n", "\r"], "\n", $this->getTableDefinition($model)));

        foreach ($lines as $line) {
            $line = trim($line, ', ');
            $line = preg_replace('#/\*![^\s]+\s(.+?)\s\*/#ui', '$1', $line) ?? '';

            if (str_starts_with($line, 'FULLTEXT ') && preg_match($regexp, $line, $matches)) {
                $indexes[] = new FulltextIndex($matches[1], $line);
            }
        }

        return $indexes;
    }

    protected function getTableDefinition(Model $model): string {
        $connection = $model->getConnection();
        $table      = $connection->getQueryGrammar()->wrap($model->getTable());
        $show       = $connection->select("SHOW CREATE TABLE {$table}");
        $sql        = isset($show[0]) && $show[0] instanceof stdClass
            ? $show[0]->{'Create Table'}
            : '';

        return $sql;
    }

    /**
     * @param array<string> $queries
     */
    protected function execute(Model $model, array $queries): void {
        $connection = $model->getConnection();

        foreach ($queries as $query) {
            $connection->unprepared($query);
        }
    }
    // </editor-fold>
}
