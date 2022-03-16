<?php declare(strict_types = 1);

namespace App\Utils\Processor\Commands;

use App\Services\I18n\Formatter;
use App\Services\Service;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Console\Command;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Str;
use Symfony\Component\Console\Helper\ProgressBar;

use function array_unique;
use function floor;
use function implode;
use function is_a;
use function max;
use function memory_get_usage;
use function sprintf;
use function strtr;
use function time;

abstract class ProcessorCommand extends Command {
    public function __construct() {
        $replacements      = $this->getReplacements();
        $this->signature   = strtr(
            $this->signature ?? $this->getDefaultCommandSignature(),
            $replacements,
        );
        $this->description = Str::ucfirst(strtr(
            $this->description ?? $this->getDefaultCommandDescription(),
            $replacements,
        ));

        parent::__construct();
    }

    protected function process(Formatter $formatter, Processor $processor): int {
        // Prepare
        $progress = $this->output->createProgressBar();
        $offset   = $this->hasOption('offset')
            ? ($this->option('offset') ?: null)
            : null;
        $chunk    = $this->hasOption('chunk')
            ? (((int) $this->option('chunk')) ?: null)
            : null;
        $limit    = $this->hasOption('limit')
            ? (((int) $this->option('limit')) ?: null)
            : null;

        // Keys?
        if ($processor instanceof EloquentProcessor && $this->hasArgument('id')) {
            $keys      = array_unique((array) $this->argument('id')) ?: null;
            $processor = $processor->setKeys($keys);
        }

        // Style
        // [▓▓░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]   3%
        // ! 999:99:99 T: 115 000 000 P:   5 000 000 S:   5 000 000 F:         123
        // ~ 000:25:15 M:  143.05 MiB O:      e706aa47-4c00-4ffa-a1ac-bb10f4ada3b6
        $progress->setBarWidth(64 - 3);
        $progress->setFormat(implode("\n", [
            '[%bar%] %progress:6.6s%%',
            '! %time-elapsed:9.9s% T: %state-total:11.11s% P: %state-processed:11.11s% S: <info>%state-success:11.11s%</info> F: <comment>%state-failed:11.11s%</comment>', // @phpcs:ignore Generic.Files.LineLength.TooLong
            '~ %time-remaining:9.9s% M: %usage-memory:11.11s% O: %state-offset:41s%',
        ]));

        $this->describeProgressBar($formatter, $progress);
        $progress->start();

        // Process
        $sync = function (State $state) use ($formatter, $progress): void {
            $this->updateProgressBar($formatter, $progress, $state);

            $progress->setProgress($state->processed);
        };

        $processor
            ->setChunkSize($chunk)
            ->setOffset($offset)
            ->setLimit($limit)
            ->onInit(function (State $state) use ($formatter, $progress): void {
                if ($state->total !== null) {
                    $progress->setMaxSteps(max($state->total, $state->processed));
                }

                $this->updateProgressBar($formatter, $progress, $state);
                $progress->display();
            })
            ->onFinish(function (State $state) use ($formatter, $progress): void {
                $this->updateProgressBar($formatter, $progress, $state);

                $progress->finish();
            })
            ->onChange($sync)
            ->onReport($sync)
            ->onProcess($sync)
            ->start();

        // Done
        $this->newLine(2);
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }

    /**
     * @return array<string,string>
     */
    protected function getReplacements(): array {
        $service = Str::snake($this->getReplacementsServiceName(), '-');
        $command = Str::snake($this->getReplacementsCommandName(), '-');
        $objects = Str::before($command, '-');
        $action  = Str::after($command, '-');

        return [
            '${command}' => "ep:{$service}-{$command}",
            '${objects}' => $objects,
            '${object}'  => Str::singular($objects),
            '${action}'  => $action,
        ];
    }

    protected function getReplacementsServiceName(): string {
        $name = Service::getServiceName($this);

        if (!$name) {
            throw new LogicException('Each command must be associated with Service.');
        }

        return $name;
    }

    protected function getReplacementsCommandName(): string {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * @param array<string> $signature
     *
     * @return array<string>
     */
    protected function getCommandSignature(array $signature): array {
        return $signature;
    }

    private function getDefaultCommandSignature(): string {
        $processor = $this->getProcessorClass();
        $signature = [
            '${command}',
            '{--offset= : start processing from given offset}',
            '{--limit=  : max ${objects} to process}',
            '{--chunk=  : chunk size}',
        ];

        if (is_a($processor, EloquentProcessor::class, true)) {
            $signature[] = '{id?* : process only these ${objects} (if empty all ${objects} will be processed)}';
        }

        return implode("\n", $this->getCommandSignature($signature));
    }

    private function getDefaultCommandDescription(): string {
        return '${action} ${objects}.';
    }

    /**
     * @return class-string<Processor>
     */
    protected function getProcessorClass(): string {
        $class     = new ReflectionClass($this);
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

    private function updateProgressBar(Formatter $formatter, ProgressBar $progress, State $state): void {
        $progress->setMessage(
            $progress->getMaxSteps()
                ? $formatter->decimal($progress->getProgressPercent() * 100, 2)
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
            $formatter->filesize(memory_get_usage(true)),
            'usage-memory',
        );
        $progress->setMessage(
            $state->total
                ? $formatter->integer($state->total)
                : '?',
            'state-total',
        );
        $progress->setMessage(
            $formatter->integer($state->processed),
            'state-processed',
        );
        $progress->setMessage(
            $formatter->integer($state->success),
            'state-success',
        );
        $progress->setMessage(
            $formatter->integer($state->failed),
            'state-failed',
        );
        $progress->setMessage(
            (string) ($state->offset ?? '?'),
            'state-offset',
        );
    }

    private function describeProgressBar(Formatter $formatter, ProgressBar $progress): void {
        $progress->setMessage('???.??', 'progress');
        $progress->setMessage('elapsed', 'time-elapsed');
        $progress->setMessage('remaining', 'time-remaining');
        $progress->setMessage('memory', 'usage-memory');
        $progress->setMessage('total', 'state-total');
        $progress->setMessage('processed', 'state-processed');
        $progress->setMessage('success', 'state-success');
        $progress->setMessage('failed', 'state-failed');
        $progress->setMessage('offset', 'state-offset');
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
