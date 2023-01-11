<?php declare(strict_types = 1);

namespace App\Utils\Processor\Commands;

use App\Services\I18n\Formatter;
use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Contracts\Offsetable;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\Contracts\StateStore;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\IteratorProcessor;
use App\Utils\Processor\State;
use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mockery;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @covers \App\Utils\Processor\Commands\ProcessorCommand
 */
class ProcessorCommandTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderConstruct
     *
     * @param class-string<Command> $command
     */
    public function testConstruct(string $expected, string $command): void {
        $buffer  = new BufferedOutput();
        $command = $this->app->make($command);

        (new DescriptorHelper())->describe($buffer, $command);

        self::assertEquals($expected, $buffer->fetch());
    }

    public function testProcessGenericProcessor(): void {
        $chunk     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $limit     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $offset    = $this->faker->uuid();
        $buffer    = new BufferedOutput();
        $progress  = new ProgressBar($buffer);
        $formatter = Mockery::mock(Formatter::class);
        $processor = Mockery::mock(IteratorProcessor::class);
        $processor
            ->shouldReceive('setLimit')
            ->with($limit)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setOffset')
            ->with($offset)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setChunkSize')
            ->with($chunk)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onInit')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onChange')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onFinish')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onReport')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onProcess')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setStore')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('start')
            ->once()
            ->andReturns();

        $input = Mockery::mock(InputInterface::class);
        $input
            ->shouldReceive('hasOption')
            ->with('offset')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('offset')
            ->once()
            ->andReturn($offset);
        $input
            ->shouldReceive('hasOption')
            ->with('chunk')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('chunk')
            ->once()
            ->andReturn($chunk);
        $input
            ->shouldReceive('hasOption')
            ->with('limit')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('limit')
            ->once()
            ->andReturn($limit);
        $input
            ->shouldReceive('hasOption')
            ->with('state')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('state')
            ->once()
            ->andReturn(null);

        $output = Mockery::mock(OutputStyle::class);
        $output
            ->shouldReceive('createProgressBar')
            ->once()
            ->andReturn($progress);
        $output
            ->shouldReceive('newLine')
            ->twice()
            ->andReturns();
        $output
            ->shouldReceive('writeln')
            ->with('<info>Done.</info>', OutputInterface::VERBOSITY_NORMAL)
            ->once()
            ->andReturns();

        $command = new class($formatter) extends ProcessorCommand {
            /**
             * @param IteratorProcessor<mixed, mixed, State> $processor
             */
            public function __invoke(IteratorProcessor $processor): int {
                return $this->process($processor);
            }

            protected static function getReplacementsServiceName(): string {
                return 'Test';
            }
        };

        $command->setInput($input);
        $command->setOutput($output);

        $expected = Command::SUCCESS;
        $actual   = $command($processor);

        self::assertEquals($expected, $actual);
    }

    public function testProcessEloquentProcessor(): void {
        $keys      = $this->faker->randomElement([null, [$this->faker->randomNumber()]]);
        $chunk     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $limit     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $offset    = $this->faker->uuid();
        $buffer    = new BufferedOutput();
        $progress  = new ProgressBar($buffer);
        $formatter = Mockery::mock(Formatter::class);
        $processor = Mockery::mock(EloquentProcessor::class);
        $processor
            ->shouldReceive('setKeys')
            ->with($keys)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setLimit')
            ->with($limit)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setOffset')
            ->with($offset)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setChunkSize')
            ->with($chunk)
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onInit')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onChange')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onFinish')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onReport')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('onProcess')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('setStore')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('start')
            ->once()
            ->andReturns();

        $input = Mockery::mock(InputInterface::class);
        $input
            ->shouldReceive('hasArgument')
            ->with('id')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getArgument')
            ->with('id')
            ->once()
            ->andReturn($keys);
        $input
            ->shouldReceive('hasOption')
            ->with('offset')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('offset')
            ->once()
            ->andReturn($offset);
        $input
            ->shouldReceive('hasOption')
            ->with('chunk')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('chunk')
            ->once()
            ->andReturn($chunk);
        $input
            ->shouldReceive('hasOption')
            ->with('limit')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('limit')
            ->once()
            ->andReturn($limit);
        $input
            ->shouldReceive('hasOption')
            ->with('state')
            ->once()
            ->andReturn(true);
        $input
            ->shouldReceive('getOption')
            ->with('state')
            ->once()
            ->andReturn(null);

        $output = Mockery::mock(OutputStyle::class);
        $output
            ->shouldReceive('createProgressBar')
            ->once()
            ->andReturn($progress);
        $output
            ->shouldReceive('newLine')
            ->twice()
            ->andReturns();
        $output
            ->shouldReceive('writeln')
            ->with('<info>Done.</info>', OutputInterface::VERBOSITY_NORMAL)
            ->once()
            ->andReturns();

        $command = new class($formatter) extends ProcessorCommand {
            /**
             * @param IteratorProcessor<mixed, mixed, State> $processor
             */
            public function __invoke(IteratorProcessor $processor): int {
                return $this->process($processor);
            }

            protected static function getReplacementsServiceName(): string {
                return 'Test';
            }
        };

        $command->setInput($input);
        $command->setOutput($output);

        $expected = Command::SUCCESS;
        $actual   = $command($processor);

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string, class-string<Command>}>
     */
    public function dataProviderConstruct(): array {
        return [
            // @phpcs:disable Generic.Files.LineLength.TooLong
            ProcessorCommand__ProcessorProcess::class           => [
                <<<'HELP'
                Description:
                  Process Processor.

                Usage:
                  ep:test-processor-process [options]

                Options:
                      --state[=STATE]  Initial state, allows to continue processing (overwrites other options except `--chunk`)
                      --chunk[=CHUNK]  Chunk size

                HELP,
                ProcessorCommand__ProcessorProcess::class,
            ],
            ProcessorCommand__ProcessorLimitableProcess::class  => [
                <<<'HELP'
                Description:
                  Process ProcessorLimitable.

                Usage:
                  ep:test-processor-limitable-process [options]

                Options:
                      --state[=STATE]  Initial state, allows to continue processing (overwrites other options except `--chunk`)
                      --chunk[=CHUNK]  Chunk size
                      --limit[=LIMIT]  Maximum number of ProcessorLimitable to process

                HELP,
                ProcessorCommand__ProcessorLimitableProcess::class,
            ],
            ProcessorCommand__ProcessorOffsetableProcess::class => [
                <<<'HELP'
                Description:
                  Process ProcessorOffsetable.

                Usage:
                  ep:test-processor-offsetable-process [options]

                Options:
                      --state[=STATE]    Initial state, allows to continue processing (overwrites other options except `--chunk`)
                      --chunk[=CHUNK]    Chunk size
                      --offset[=OFFSET]  Start processing from given offset

                HELP,
                ProcessorCommand__ProcessorOffsetableProcess::class,
            ],
            ProcessorCommand__IteratorProcessorProcess::class   => [
                <<<'HELP'
                Description:
                  Process IteratorProcessor.

                Usage:
                  ep:test-iterator-processor-process [options]

                Options:
                      --state[=STATE]    Initial state, allows to continue processing (overwrites other options except `--chunk`)
                      --chunk[=CHUNK]    Chunk size
                      --limit[=LIMIT]    Maximum number of IteratorProcessor to process
                      --offset[=OFFSET]  Start processing from given offset

                HELP,
                ProcessorCommand__IteratorProcessorProcess::class,
            ],
            ProcessorCommand__EloquentProcessorProcess::class   => [
                <<<'HELP'
                Description:
                  Process EloquentProcessor.

                Usage:
                  ep:test-eloquent-processor-process [options] [--] [<id>...]

                Arguments:
                  id                     Process only these EloquentProcessor (if empty all EloquentProcessor will be processed)

                Options:
                      --state[=STATE]    Initial state, allows to continue processing (overwrites other options except `--chunk`)
                      --chunk[=CHUNK]    Chunk size
                      --limit[=LIMIT]    Maximum number of EloquentProcessor to process
                      --offset[=OFFSET]  Start processing from given offset

                HELP,
                ProcessorCommand__EloquentProcessorProcess::class,
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends ProcessorCommand<IteratorProcessor<mixed,mixed,State>>
 */
abstract class ProcessorCommand__Command extends ProcessorCommand {
    protected static function getReplacementsServiceName(): string {
        return 'Test';
    }

    protected static function getReplacementsCommandName(): string {
        return Str::after(parent::getReplacementsCommandName(), '__');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__IteratorProcessorProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__IteratorProcessor $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends IteratorProcessor<mixed,mixed,State>
 */
class ProcessorCommand__IteratorProcessor extends IteratorProcessor {
    protected function getTotal(State $state): ?int {
        throw new Exception('should not be called');
    }

    protected function getIterator(State $state): ObjectIterator {
        throw new Exception('should not be called');
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        throw new Exception('should not be called');
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        throw new Exception('should not be called');
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__EloquentProcessorProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__EloquentProcessor $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends EloquentProcessor<Model,mixed,EloquentState<Model>>
 */
class ProcessorCommand__EloquentProcessor extends EloquentProcessor {
    protected function getModel(): string {
        throw new Exception('should not be called');
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        throw new Exception('should not be called');
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        throw new Exception('should not be called');
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__ProcessorProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__Processor $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @implements Processor<Model,mixed,State>
 */
class ProcessorCommand__Processor implements Processor {
    public function getChunkSize(): int {
        throw new Exception('should not be called');
    }

    public function setChunkSize(?int $chunk): static {
        throw new Exception('should not be called');
    }

    public function isStopped(): bool {
        throw new Exception('should not be called');
    }

    public function isRunning(): bool {
        throw new Exception('should not be called');
    }

    public function getState(): ?State {
        throw new Exception('should not be called');
    }

    public function getStore(): ?StateStore {
        throw new Exception('should not be called');
    }

    public function setStore(?StateStore $store): static {
        throw new Exception('should not be called');
    }

    public function start(): bool {
        throw new Exception('should not be called');
    }

    public function stop(): void {
        throw new Exception('should not be called');
    }

    public function reset(): void {
        throw new Exception('should not be called');
    }

    public function onInit(?Closure $closure): static {
        throw new Exception('should not be called');
    }

    public function onChange(?Closure $closure): static {
        throw new Exception('should not be called');
    }

    public function onFinish(?Closure $closure): static {
        throw new Exception('should not be called');
    }

    public function onProcess(?Closure $closure): static {
        throw new Exception('should not be called');
    }

    public function onReport(?Closure $closure): static {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__ProcessorLimitableProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__ProcessorLimitable $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__ProcessorLimitable extends ProcessorCommand__Processor implements Limitable {
    public function getLimit(): ?int {
        throw new Exception('should not be called');
    }

    public function setLimit(?int $limit): static {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__ProcessorOffsetableProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__ProcessorOffsetable $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__ProcessorOffsetable extends ProcessorCommand__Processor implements Offsetable {
    public function getOffset(): string|int|null {
        throw new Exception('should not be called');
    }

    public function setOffset(int|string|null $offset): static {
        throw new Exception('should not be called');
    }
}
