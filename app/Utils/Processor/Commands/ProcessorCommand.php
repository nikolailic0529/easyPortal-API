<?php declare(strict_types = 1);

namespace App\Utils\Processor\Commands;

use App\Services\Service;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Console\Command;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Str;

use function array_unique;
use function implode;
use function is_a;
use function strtr;

abstract class ProcessorCommand extends Command {
    public function __construct() {
        $replacements      = $this->getReplacements();
        $this->signature   = strtr($this->signature ?? $this->getDefaultCommandSignature(), $replacements);
        $this->description = Str::ucfirst(
            strtr($this->description ?? $this->getDefaultCommandDescription(), $replacements),
        );

        parent::__construct();
    }

    protected function process(Processor $processor): int {
        // Prepare
        $progress = $this->output->createProgressBar();
        $offset   = $this->option('offset');
        $chunk    = ((int) $this->option('chunk')) ?: null;
        $limit    = ((int) $this->option('limit')) ?: null;

        // Keys?
        if ($processor instanceof EloquentProcessor) {
            $keys      = array_unique((array) $this->argument('id')) ?: null;
            $processor = $processor->setKeys($keys);
        }

        // Process
        $processor
            ->setChunkSize($chunk)
            ->setOffset($offset)
            ->setLimit($limit)
            ->onInit(static function (State $state) use ($progress): void {
                if ($state->total) {
                    $progress->setMaxSteps($state->total);
                }
            })
            ->onChange(static function (State $state) use ($progress): void {
                $progress->setProgress($state->processed);
            })
            ->start();

        $progress->finish();

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
        $service = Str::lower($this->getReplacementsServiceName());
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

        return implode("\n", $signature);
    }

    private function getDefaultCommandDescription(): string {
        return '${action} ${objects}.';
    }

    /**
     * @return class-string<\App\Utils\Processor\Processor>
     */
    private function getProcessorClass(): string {
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
}
