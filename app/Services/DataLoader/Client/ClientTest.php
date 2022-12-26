<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Events\RequestFailed;
use App\Services\DataLoader\Client\Events\RequestSuccessful;
use App\Services\DataLoader\Client\Exceptions\DataLoaderRequestRateTooLarge;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Client\GraphQL\GraphQL;
use App\Services\DataLoader\Schema\Schema;
use GraphQL\Utils\BuildClientSchema;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempDirectory;
use Mockery;
use ReflectionClass;
use SplFileInfo;
use Tests\Helpers\ClassMap;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function array_filter;
use function file_put_contents;
use function preg_quote;
use function preg_replace;
use function str_replace;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client\Client
 */
class ClientTest extends TestCase {
    use WithGraphQLSchema;
    use WithTempDirectory;

    /**
     * @group integration
     *
     * @coversNothing
     */
    public function testSchema(): void {
        $client = $this->app->make(Client::class);

        if (!$client->isEnabled()) {
            self::markTestSkipped('DataLoader is disabled.');
        }

        $into     = $client->getIntrospection();
        $actual   = BuildClientSchema::build($into);
        $actual   = (string) $this
            ->getGraphQLSchemaPrinter(
                (new TestSettings())->setPrintUnusedDefinitions(true),
            )
            ->printSchema($actual);
        $expected = $this->getTestData(Schema::class)->file('.graphql');

        self::assertGraphQLSchemaCompatible(
            $expected,
            $actual,
        );
    }

    public function testQueries(): void {
        $map     = [];
        $path    = $this->getTempDirectory();
        $schema  = $this->getTestData(Schema::class)->file('.graphql');
        $queries = ClassMap::get()
            ->filter(static function (ReflectionClass $class): bool {
                return $class->isSubclassOf(GraphQL::class)
                    && !$class->isAbstract();
            });

        foreach ($queries as $query) {
            $graphql    = $query->newInstance();
            $name       = str_replace('\\', '_', $query->getName());
            $file       = "{$path}/{$name}.graphql";
            $map[$file] = $query->getFileName();

            self::assertNotFalse(
                file_put_contents($file, (string) $graphql),
            );
        }

        $this->assertGraphQLQueryValid(
            new SplFileInfo($path),
            $schema,
            '',
            static function (string $message) use ($map): string {
                foreach (array_filter($map) as $tmp => $file) {
                    $tmp     = preg_quote($tmp, '/');
                    $message = preg_replace("/{$tmp}(:[^\\d])?/iu", $file, $message) ?: $message;
                }

                return $message;
            },
        );
    }

    /**
     * @covers ::call
     */
    public function testCallQuerySuccessful(): void {
        $selector  = 'data';
        $graphql   = 'query { item { id } }';
        $variables = [];
        $files     = [];
        $result    = [
            'item' => [
                'id' => $this->faker->uuid(),
            ],
        ];

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher
            ->shouldReceive('dispatch')
            ->with(Mockery::on(static function (mixed $event): bool {
                return $event instanceof RequestSuccessful;
            }))
            ->once()
            ->andReturns();

        $handler = Mockery::mock(ExceptionHandler::class);
        $config  = Mockery::mock(Repository::class);
        $client  = Mockery::mock(Factory::class);
        $token   = Mockery::mock(Token::class);
        $client  = Mockery::mock(Client::class, [$handler, $dispatcher, $config, $client, $token]);
        $client->shouldAllowMockingProtectedMethods();
        $client->makePartial();
        $client
            ->shouldReceive('callExecute')
            ->once()
            ->andReturn([
                'data' => $result,
            ]);

        self::assertEquals($result, $client->call($selector, $graphql, $variables, $files));
    }

    /**
     * @covers ::call
     */
    public function testCallQueryError(): void {
        $selector  = 'data';
        $graphql   = 'query { item { id } }';
        $variables = [];
        $files     = [];

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher
            ->shouldReceive('dispatch')
            ->with(Mockery::on(static function (mixed $event): bool {
                return $event instanceof RequestFailed;
            }))
            ->once()
            ->andReturns();

        $handler = Mockery::mock(ExceptionHandler::class);
        $config  = Mockery::mock(Repository::class);
        $client  = Mockery::mock(Factory::class);
        $token   = Mockery::mock(Token::class);
        $client  = Mockery::mock(Client::class, [$handler, $dispatcher, $config, $client, $token]);
        $client->shouldAllowMockingProtectedMethods();
        $client->makePartial();
        $client
            ->shouldReceive('callExecute')
            ->once()
            ->andReturn([
                'errors' => [
                    [
                        'message' => 'error',
                    ],
                ],
            ]);

        self::expectException(GraphQLRequestFailed::class);

        $client->call($selector, $graphql, $variables, $files);
    }

    /**
     * @covers ::call
     */
    public function testCallRequestRateTooLarge(): void {
        $selector  = 'data';
        $graphql   = 'query { item { id } }';
        $variables = [];
        $files     = [];

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher
            ->shouldReceive('dispatch')
            ->with(Mockery::on(static function (mixed $event): bool {
                return $event instanceof RequestFailed;
            }))
            ->once()
            ->andReturns();

        $handler = Mockery::mock(ExceptionHandler::class);
        $config  = Mockery::mock(Repository::class);
        $client  = Mockery::mock(Factory::class);
        $token   = Mockery::mock(Token::class);
        $client  = Mockery::mock(Client::class, [$handler, $dispatcher, $config, $client, $token]);
        $client->shouldAllowMockingProtectedMethods();
        $client->makePartial();
        $client
            ->shouldReceive('callExecute')
            ->once()
            ->andReturn([
                'errors' => [
                    [
                        'message' => 'error',
                    ],
                    [
                        'message' => 'Request rate is large. More CosmosDB Request Units may be needed',
                    ],
                ],
            ]);

        self::expectException(DataLoaderRequestRateTooLarge::class);

        $client->call($selector, $graphql, $variables, $files);
    }
}
