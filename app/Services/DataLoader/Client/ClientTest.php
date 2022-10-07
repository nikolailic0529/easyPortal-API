<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\GraphQL\GraphQL;
use App\Services\DataLoader\Schema\Schema;
use GraphQL\Utils\BuildClientSchema;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempDirectory;
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
    // </editor-fold>
}
