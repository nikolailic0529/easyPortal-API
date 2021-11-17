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
    public function testForbiddenDirectives(string $directive, ?string $replacement, ?string $regexp): void {
        $path = $this->app->make(Repository::class)->get('lighthouse.schema.register');
        $path = dirname($path);

        if (!$regexp) {
            $name   = preg_quote($directive, '/');
            $regexp = "/(^|\s+){$name}(^|\s+)/ui";
        }

        $finder = Finder::create()->in($path)->files()->contains($regexp)->sortByName();
        $usages = [];

        foreach ($finder as $file) {
            $usages[$file->getPathname()] = true;
        }

        if ($replacement) {
            $replacement = "Directive {$directive} is forbidden, {$replacement} should be used instead.";
        } else {
            $replacement = "Directive {$directive} is forbidden.";
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
            ['@guard', '@me', null],
            ['@orderBy', '@sortBy', null],
            ['@whereConditions', '@searchBy', null],
            ['@whereHasConditions', '@searchBy', null],
            ['@spread', null, null],
            ['@hash', null, null],
            ['@globalId', null, null],
            ['@trim', null, null],
            ['@rename', null, null],
            ['@paginate', '@paginated', null],
            ['@hasMany(type: xxx)', '@hasMany @paginatedRelation', '/@hasMany\(.*?type:.*?\)/ui'],
            ['@belongsToMany(type: xxx)', '@belongsToMany @paginatedRelation', '/@belongsToMany\(.*?type:.*?\)/ui'],
            ['@morphMany(type: xxx)', '@morphMany @paginatedRelation', '/@morphMany\(.*?type:.*?\)/ui'],
            ['@paginatedLimit', null, null],
            ['@paginatedOffset', null, null],
            ['@cache', '@cached', null],
            ['@cacheKey', '@cached', null],
        ];
    }
    //</editor-fold>
}
