<?php declare(strict_types = 1);

namespace App\Utils\Processor\Commands;

use App\Services\I18n\Formatter;
use App\Services\Service;
use App\Utils\Console\WithOptions;
use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\Offsetable;
use App\Utils\Processor\CompositeProcessor;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\State;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\Console\Helper\ProgressBar;

use function array_unique;
use function explode;
use function filter_var;
use function floor;
use function implode;
use function is_a;
use function max;
use function memory_get_usage;
use function min;
use function reset;
use function sprintf;
use function strtr;
use function time;
use function trim;

use const FILTER_VALIDATE_INT;
use const PHP_EOL;

/**
 * Allow to run any {@see Processor} as a console command. It is also store the
 * Processor state in the cache so User can resume execution if command was
 * interrupted/unsuccessful.
 *
 * @template TProcessor of Processor
 */
abstract class ProcessorCommand extends Command {
    use WithOptions;

    public function __construct(
        private Formatter $formatter,
    ) {
        $replacements      = static::getReplacements();
        $this->signature   = strtr(
            $this->signature ?: self::getDefaultCommandSignature(),
            $replacements,
        );
        $this->description = Str::ucfirst(strtr(
            $this->description ?: self::getDefaultCommandDescription(),
            $replacements,
        ));

        parent::__construct();
    }

    /**
     * @param TProcessor $processor
     */
    protected function process(Processor $processor): int {
        // Prepare
        $progress = $this->output->createProgressBar();
        $service  = $this->getService();
        $chunk    = $this->getIntOption('chunk');
        $state    = $this->getStringOption('state') ?: Str::uuid()->toString();
        $store    = $service
            ? new ProcessorStateStore($service, $this, $state)
            : null;

        // Keys?
        if ($processor instanceof EloquentProcessor && $this->hasArgument('id')) {
            $keys      = array_unique((array) $this->argument('id')) ?: null;
            $processor = $processor->setKeys($keys);
        }

        // Style & Settings
        // Operation name                                                  10 / 12
        // [▓▓░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]   3%
        // ! 999:99:99 T: 115 000 000 P:   5 000 000 S:   5 000 000 F:         123
        // ~ 000:25:15 M:  143.05 MiB S:      e706aa47-4c00-4ffa-a1ac-bb10f4ada3b6
        $progress->setBarWidth(64 - 3);
        $progress->setFormat(implode(PHP_EOL, [
            '%operation-name:-63.63s% %operation-index:2.2s% / %operation-total:2.2s%',
            '[%bar%] %progress:6.6s%%',
            '! %time-elapsed:9.9s% T: %state-total:11.11s% P: %state-processed:11.11s% S: <info>%state-success:11.11s%</info> F: <comment>%state-failed:11.11s%</comment>', // @phpcs:ignore Generic.Files.LineLength.TooLong
            '~ %time-remaining:9.9s% M: %usage-memory:11.11s% S: %state-uuid:41s%',
        ]));

        $this->describeProgressBar($progress);
        $progress->start();

        // Process
        $state = null;
        $sync  = function (State $state) use ($progress, $store): void {
            $this->updateProgressBar($progress, $store, $state);
        };

        if ($processor instanceof Limitable) {
            $limit     = $this->getIntOption('limit');
            $processor = $processor->setLimit($limit);
        }

        if ($processor instanceof Offsetable) {
            $offset    = $this->getStringOption('offset');
            $processor = $processor->setOffset($offset);
        }

        $processor
            ->setStore($store)
            ->setChunkSize($chunk)
            ->onInit(static function (State $current) use ($progress, $sync): void {
                $sync($current);

                $progress->display();
            })
            ->onFinish(static function (State $current) use ($progress, $sync, &$state): void {
                $state = $current;

                $sync($current);

                $progress->finish();
            })
            ->onChange($sync)
            ->onReport($sync)
            ->onProcess($sync)
            ->start();

        // Summary
        if ($processor instanceof CompositeProcessor && $state instanceof CompositeState) {
            $this->showCompositeProcessorSummary($processor, $state);
        } else {
            $this->newLine();
        }

        // Done
        $this->newLine();
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }

    public static function getDefaultName(): string {
        $service = Str::snake(static::getReplacementsServiceName(), '-');
        $command = Str::snake(static::getReplacementsCommandName(), '-');
        $name    = "ep:{$service}-{$command}";

        return $name;
    }

    /**
     * @return array<string,string>
     */
    protected static function getReplacements(): array {
        $command = Str::snake(static::getReplacementsCommandName(), '-');
        $action  = Str::afterLast($command, '-');
        $objects = Str::studly(Str::beforeLast($command, '-'));

        return [
            '${command}' => static::getDefaultName(),
            '${objects}' => $objects,
            '${object}'  => Str::singular($objects),
            '${action}'  => $action,
        ];
    }

    protected static function getReplacementsServiceName(): string {
        $name = Service::getServiceName(static::class);

        if (!$name) {
            throw new LogicException('Each command must be associated with Service.');
        }

        return $name;
    }

    protected static function getReplacementsCommandName(): string {
        return (new ReflectionClass(static::class))->getShortName();
    }

    /**
     * @param array<string> $signature
     *
     * @return array<string>
     */
    protected static function getCommandSignature(array $signature): array {
        return $signature;
    }

    private static function getDefaultCommandSignature(): string {
        // Default
        $processor = static::getProcessorClass();
        $signature = [
            '${command}',
            '{--state= : Initial state, allows to continue processing (overwrites other options except `--chunk`)}',
            '{--chunk= : Chunk size}',
        ];

        // Limit & Offset
        if (is_a($processor, Limitable::class, true)) {
            $signature[] = '{--limit= : Maximum number of ${objects} to process}';
        }

        if (is_a($processor, Offsetable::class, true)) {
            $signature[] = '{--offset= : Start processing from given offset}';
        }

        // Eloquent?
        if (is_a($processor, EloquentProcessor::class, true)) {
            $signature[] = '{id?* : Process only these ${objects} (if empty all ${objects} will be processed)}';
        }

        // Return
        return implode("\n", static::getCommandSignature($signature));
    }

    private static function getDefaultCommandDescription(): string {
        return '${action} ${objects}.';
    }

    /**
     * @return class-string<TProcessor>
     */
    protected static function getProcessorClass(): string {
        $class     = new ReflectionClass(static::class);
        $method    = $class->getMethod('__invoke');
        $processor = null;

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && is_a($type->getName(), Processor::class, true)) {
                $processor = $type->getName();
                break;
            }
        }

        if (!$processor) {
            throw new LogicException('Impossible to determine Processor.');
        }

        return $processor;
    }

    protected function getDefaultOperationName(): string {
        $lines = explode("\n", $this->getDescription());
        $name  = trim(trim(reset($lines) ?: ''), '.');

        return $name;
    }

    private function updateProgressBar(
        ProgressBar $progress,
        ?ProcessorStateStore $store,
        State $state,
    ): void {
        // Operation
        $description = $this->getDefaultOperationName();

        if ($state instanceof CompositeState) {
            $progress->setMessage($state->getCurrentOperationName() ?? $description, 'operation-name');
            $progress->setMessage((string) min($state->index + 1, $state->total), 'operation-index');
            $progress->setMessage((string) $state->total, 'operation-total');
        } else {
            $progress->setMessage($description, 'operation-name');
            $progress->setMessage('1', 'operation-index');
            $progress->setMessage('1', 'operation-total');
        }

        // Overwrite
        if ($state instanceof CompositeState) {
            $state = $state->getCurrentOperationState() ?? $state;
        }

        if ($state->total !== null) {
            $progress->setMaxSteps(max($state->total, $state->processed));
        } else {
            $progress->setMaxSteps(0);
        }

        $progress->setProgress($state->processed);

        // Progress
        $progress->setMessage(
            $progress->getMaxSteps()
                ? $this->formatter->decimal($progress->getProgressPercent() * 100, 2)
                : '???.??',
            'progress',
        );
        $progress->setMessage(
            $this->duration(time() - $progress->getStartTime()),
            'time-elapsed',
        );
        $progress->setMessage(
            $progress->getMaxSteps()
                ? $this->duration($progress->getRemaining())
                : '???:??:??',
            'time-remaining',
        );
        $progress->setMessage(
            $this->formatter->filesize(memory_get_usage(true)),
            'usage-memory',
        );
        $progress->setMessage(
            $state->total !== null
                ? $this->formatter->integer($state->total)
                : '?',
            'state-total',
        );
        $progress->setMessage(
            $this->formatter->integer($state->processed),
            'state-processed',
        );
        $progress->setMessage(
            $this->formatter->integer($state->success),
            'state-success',
        );
        $progress->setMessage(
            $this->formatter->integer($state->failed),
            'state-failed',
        );
        $progress->setMessage(
            $store ? $store->getUuid() : 'unavailable',
            'state-uuid',
        );
    }

    private function describeProgressBar(ProgressBar $progress): void {
        $progress->setMessage('???.??', 'progress');
        $progress->setMessage('elapsed', 'time-elapsed');
        $progress->setMessage('remaining', 'time-remaining');
        $progress->setMessage('memory', 'usage-memory');
        $progress->setMessage('total', 'state-total');
        $progress->setMessage('processed', 'state-processed');
        $progress->setMessage('success', 'state-success');
        $progress->setMessage('failed', 'state-failed');
        $progress->setMessage('state', 'state-uuid');
        $progress->setMessage($this->getDefaultOperationName(), 'operation-name');
        $progress->setMessage('?', 'operation-index');
        $progress->setMessage('?', 'operation-total');
    }

    /**
     * @param CompositeProcessor<CompositeState> $processor
     */
    private function showCompositeProcessorSummary(
        CompositeProcessor $processor,
        CompositeState $state,
    ): void {
        $operations = $processor->getOperationsState($state);
        $integer    = function (mixed $value, string $style = null): string {
            $value = filter_var($value, FILTER_VALIDATE_INT);

            if ($value !== false) {
                $zero  = $value === 0;
                $value = $this->formatter->integer($value);

                if ($style && !$zero) {
                    $value = "<{$style}>{$value}</{$style}>";
                }
            } else {
                $value = '-';
            }

            return $value;
        };
        $summary    = [];

        foreach ($operations as $operation) {
            $operationName  = $operation['name'];
            $operationState = $operation['state'];

            if ($operationState instanceof State) {
                $summary[] = [
                    $operationName,
                    $integer($operationState->total),
                    $integer($operationState->processed),
                    $integer($operationState->success, 'info'),
                    $integer($operationState->failed, 'comment'),
                ];
            } else {
                $summary[] = [
                    $operationName,
                    '-',
                    '-',
                    '-',
                    '-',
                ];
            }
        }

        $this->newLine(2);
        $this->table(
            ['Operation', 'Total', 'Processed', 'Success', 'Failed'],
            $summary,
        );
    }

    protected function getService(): ?Service {
        $class   = Service::getService($this);
        $service = $class ? $this->laravel->make($class) : null;

        return $service;
    }

    private function duration(int|float $duration): string {
        $parts = [
            'hours'   => 0,
            'minutes' => 0,
            'seconds' => 0,
        ];
        $units = [
            'hours'   => 60 * 60,
            'minutes' => 60,
            'seconds' => 1,
        ];

        foreach ($units as $unit => $factor) {
            $parts[$unit] = (int) floor($duration / $factor);
            $duration     = $duration - $parts[$unit] * $factor;
        }

        if ($parts['hours'] > 999) {
            $parts['hours'] = 999;
        }

        return sprintf(
            '%1$03.3s:%2$02s:%3$02s',
            $parts['hours'],
            $parts['minutes'],
            $parts['seconds'],
        );
    }
}
