<?php declare(strict_types = 1);

namespace App\Utils\Processor\Commands;

use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\EloquentProcessor;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
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
 * @coversDefaultClass \App\Utils\Processor\Commands\ProcessorCommand
 */
class ProcessorCommandTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__construct
     * @covers ::getReplacements
     * @covers ::getDefaultCommandSignature
     * @covers ::getDefaultCommandDescription
     *
     * @dataProvider dataProviderConstruct
     *
     * @param class-string<\Illuminate\Console\Command> $command
     */
    public function testConstruct(string $expected, string $command): void {
        $buffer  = new BufferedOutput();
        $command = $this->app->make($command);

        (new DescriptorHelper())->describe($buffer, $command);

        $this->assertEquals($expected, $buffer->fetch());
    }

    /**
     * @covers ::__construct
     */
    public function testProcessGenericProcessor(): void {
        $chunk     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $limit     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $offset    = $this->faker->uuid;
        $buffer    = new BufferedOutput();
        $progress  = new ProgressBar($buffer);
        $processor = Mockery::mock(Processor::class);
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
            ->shouldReceive('start')
            ->once()
            ->andReturns();

        $input = Mockery::mock(InputInterface::class);
        $input
            ->shouldReceive('getOption')
            ->with('offset')
            ->once()
            ->andReturn($offset);
        $input
            ->shouldReceive('getOption')
            ->with('chunk')
            ->once()
            ->andReturn($chunk);
        $input
            ->shouldReceive('getOption')
            ->with('limit')
            ->once()
            ->andReturn($limit);

        $output = Mockery::mock(OutputStyle::class);
        $output
            ->shouldReceive('createProgressBar')
            ->once()
            ->andReturn($progress);
        $output
            ->shouldReceive('newLine')
            ->once()
            ->andReturns();
        $output
            ->shouldReceive('writeln')
            ->with('<info>Done.</info>', OutputInterface::VERBOSITY_NORMAL)
            ->once()
            ->andReturns();

        $command = new class() extends ProcessorCommand {
            public function __invoke(Processor $processor): int {
                return $this->process($processor);
            }

            protected function process(Processor $processor): int {
                return parent::process($processor);
            }

            protected function getReplacementsServiceName(): string {
                return 'Test';
            }
        };

        $command->setInput($input);
        $command->setOutput($output);

        $expected = Command::SUCCESS;
        $actual   = $command($processor);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::__construct
     */
    public function testProcessEloquentProcessor(): void {
        $keys      = $this->faker->randomElement([null, [$this->faker->randomNumber()]]);
        $chunk     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $limit     = $this->faker->randomElement([null, $this->faker->randomNumber()]);
        $offset    = $this->faker->uuid;
        $buffer    = new BufferedOutput();
        $progress  = new ProgressBar($buffer);
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
            ->shouldReceive('start')
            ->once()
            ->andReturns();

        $input = Mockery::mock(InputInterface::class);
        $input
            ->shouldReceive('getArgument')
            ->with('id')
            ->once()
            ->andReturn($keys);
        $input
            ->shouldReceive('getOption')
            ->with('offset')
            ->once()
            ->andReturn($offset);
        $input
            ->shouldReceive('getOption')
            ->with('chunk')
            ->once()
            ->andReturn($chunk);
        $input
            ->shouldReceive('getOption')
            ->with('limit')
            ->once()
            ->andReturn($limit);

        $output = Mockery::mock(OutputStyle::class);
        $output
            ->shouldReceive('createProgressBar')
            ->once()
            ->andReturn($progress);
        $output
            ->shouldReceive('newLine')
            ->once()
            ->andReturns();
        $output
            ->shouldReceive('writeln')
            ->with('<info>Done.</info>', OutputInterface::VERBOSITY_NORMAL)
            ->once()
            ->andReturns();

        $command = new class() extends ProcessorCommand {
            public function __invoke(Processor $processor): int {
                return $this->process($processor);
            }

            protected function process(Processor $processor): int {
                return parent::process($processor);
            }

            protected function getReplacementsServiceName(): string {
                return 'Test';
            }
        };

        $command->setInput($input);
        $command->setOutput($output);

        $expected = Command::SUCCESS;
        $actual   = $command($processor);

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string, class-string<\Illuminate\Console\Command>}>
     */
    public function dataProviderConstruct(): array {
        return [
            ProcessorCommand__ObjectsProcess::class => [
                <<<'HELP'
                Description:
                  Process objects.

                Usage:
                  ep:test-objects-process [options]

                Options:
                      --offset[=OFFSET]  start processing from given offset
                      --limit[=LIMIT]    max objects to process
                      --chunk[=CHUNK]    chunk size

                HELP,
                ProcessorCommand__ObjectsProcess::class,
            ],
            ProcessorCommand__ModelsProcess::class  => [
                <<<'HELP'
                Description:
                  Process models.

                Usage:
                  ep:test-models-process [options] [--] [<id>...]

                Arguments:
                  id                     process only these models (if empty all models will be processed)

                Options:
                      --offset[=OFFSET]  start processing from given offset
                      --limit[=LIMIT]    max models to process
                      --chunk[=CHUNK]    chunk size

                HELP,
                ProcessorCommand__ModelsProcess::class,
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
 */
abstract class ProcessorCommand__Command extends ProcessorCommand {
    protected function getReplacementsServiceName(): string {
        return 'Test';
    }

    protected function getReplacementsCommandName(): string {
        return Str::after(parent::getReplacementsCommandName(), '__');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__ObjectsProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__Processor $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorCommand__Processor extends Processor {
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
class ProcessorCommand__ModelsProcess extends ProcessorCommand__Command {
    public function __invoke(ProcessorCommand__EloquentProcessor $processor): int {
        throw new Exception('should not be called');
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
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