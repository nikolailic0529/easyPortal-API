<?php declare(strict_types = 1);

namespace Tests;

use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQL\Testing\GraphQLAssertions;
use LastDragon_ru\LaraASP\GraphQL\Utils\ArgumentFactory;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use SplFileInfo;
use Symfony\Component\Process\Process;

use function file_put_contents;
use function is_string;
use function trim;

/**
 * @mixin TestCase
 */
trait WithGraphQLSchema {
    use GraphQLAssertions;

    public function assertGraphQLSchemaCompatible(
        SplFileInfo|string $expected,
        SplFileInfo|string $actual,
        string $message = '',
    ): void {
        // Prepare
        $expected = $expected instanceof SplFileInfo ? $expected : $this->getTempFile($expected, '.graphql');
        $actual   = $actual instanceof SplFileInfo ? $actual : $this->getTempFile($actual, '.graphql');

        if ((int) $expected->getSize() === 0) {
            file_put_contents($expected->getPathname(), $actual);
        }

        // Test
        $process = new Process([
            'graphql-inspector',
            'diff',
            $expected->getPathname(),
            $actual->getPathname(),
        ]);
        $process->run();

        self::assertTrue($process->isSuccessful(), trim("{$message}\n\n{$process->getOutput()}"));
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
}
