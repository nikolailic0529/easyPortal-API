<?php declare(strict_types = 1);

namespace App\GraphQL;

use Illuminate\Contracts\Config\Repository;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function dirname;
use function preg_quote;

/**
 * @coversNothing
 */
class SchemaTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testSchema(): void {
        $this->assertGraphQLSchemaEquals($this->getGraphQLSchemaExpected());
    }

    /**
     * @dataProvider dataProviderForbiddenDirectives
     */
    public function testForbiddenDirectives(string $directive, string $replacement): void {
        $path   = $this->app->make(Repository::class)->get('lighthouse.schema.register');
        $path   = dirname($path);
        $name   = preg_quote($directive, '/');
        $finder = Finder::create()->in($path)->files()->contains("/(^|\s+){$name}(^|\s+)/ui")->sortByName();
        $usages = [];

        foreach ($finder as $file) {
            $usages[$file->getPathname()] = true;
        }

        if ($replacement) {
            $replacement = "Directive {$directive} is deprecated, {$replacement} should be used instead.";
        }

        $this->assertEquals([], $usages, $replacement);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderForbiddenDirectives(): array {
        return [
            ['@guard', '@user'],
        ];
    }
    //</editor-fold>
}
