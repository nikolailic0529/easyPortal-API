<?php declare(strict_types = 1);

namespace Tests;

use LastDragon_ru\LaraASP\GraphQL\Testing\GraphQLAssertions;
use LastDragon_ru\LaraASP\GraphQL\Utils\ArgumentFactory;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use SplFileInfo;

use function file_put_contents;

/**
 * @mixin TestCase
 */
trait WithGraphQLSchema {
    use GraphQLAssertions;

    protected function getGraphQLSchemaExpected(string $schema = '.graphql', string $source = null): string {
        $data = $this->getTestData();

        if ($data->content($schema) === '') {
            if ($source !== null) {
                $source = $this->getGraphQLSchema($data->content($source));
            } else {
                $source = $this->getDefaultGraphQLSchema();
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
