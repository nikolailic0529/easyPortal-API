<?php declare(strict_types = 1);

namespace Tests;

use Closure;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQL\Testing\GraphQLAssertions;
use LastDragon_ru\LaraASP\GraphQL\Utils\ArgumentFactory;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use SplFileInfo;
use Symfony\Component\Process\Process;

use function array_merge;
use function file_put_contents;
use function is_string;
use function trim;

/**
 * @mixin TestCase
 */
trait WithGraphQLSchema {
    use GraphQLAssertions;

    /**
     * @param Closure(string):string|null $closure
     */
    public function assertGraphQLQueryValid(
        SplFileInfo|string $query,
        SplFileInfo|string $schema,
        string $message = '',
        Closure $closure = null,
    ): void {
        // Prepare
        $query  = $query instanceof SplFileInfo ? $query : $this->getTempFile($query, '.graphql');
        $schema = $schema instanceof SplFileInfo ? $schema : $this->getTempFile($schema, '.graphql');

        if ($query->isDir()) {
            $query = "{$query->getPathname()}/**/*.graphql";
        } else {
            $query = $query->getPathname();
        }

        // Test
        $this->runGraphqlInspector(
            [
                'validate',
                $query,
                $schema->getPathname(),
            ],
            $message,
            $closure,
        );
    }

    /**
     * @param Closure(string):string|null $closure
     */
    public function assertGraphQLSchemaCompatible(
        SplFileInfo|string $expected,
        SplFileInfo|string $actual,
        string $message = '',
        Closure $closure = null,
    ): void {
        // Prepare
        $expected = $expected instanceof SplFileInfo ? $expected : $this->getTempFile($expected, '.graphql');
        $actual   = $actual instanceof SplFileInfo ? $actual : $this->getTempFile($actual, '.graphql');

        if ((int) $expected->getSize() === 0) {
            file_put_contents($expected->getPathname(), $actual);
        }

        // Test
        $this->runGraphqlInspector(
            [
                'diff',
                $expected->getPathname(),
                $actual->getPathname(),
            ],
            $message,
            $closure,
        );
    }

    protected function getGraphQLSchemaExpected(string $schema = '.graphql', Schema|string $source = null): string {
        $data    = $this->getTestData();
        $content = $data->file($schema)->isFile()
            ? $data->content($schema)
            : null;

        if (!$content) {
            if (is_string($source)) {
                $source = $this->getGraphQLSchema($data->content($source));
            } elseif ($source === null) {
                $source = $this->getDefaultGraphQLSchema();
            } else {
                // empty
            }

            self::assertNotFalse(file_put_contents($data->path($schema), $this->printGraphQLSchema($source)));
        }

        return $data->content($schema);
    }

    protected function getGraphQLArgument(string $type, mixed $value, SplFileInfo|string $schema = null): Argument {
        $this->useGraphQLSchema(
            $schema ?? <<<'GRAPHQL'
            type Query {
                test: Int @all
            }
            GRAPHQL,
        );

        $factory  = $this->app->make(ArgumentFactory::class);
        $argument = $factory->getArgument($type, $value);

        return $argument;
    }

    /**
     * @param array<string>               $args
     * @param Closure(string):string|null $closure
     */
    private function runGraphqlInspector(array $args, string $message, Closure $closure = null): void {
        $process = new Process(array_merge(['graphql-inspector'], $args));
        $process->run();

        $message = trim("{$message}\n\n{$process->getOutput()}\n\n{$process->getErrorOutput()}");
        $message = $closure ? $closure($message) : $message;

        self::assertTrue($process->isSuccessful(), $message);
    }
}
