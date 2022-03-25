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
        self::assertDefaultGraphQLSchemaEquals($this->getGraphQLSchemaExpected());
    }

    /**
     * @dataProvider dataProviderForbiddenDirectives
     */
    public function testForbiddenDirectives(string $directive, ?string $replacement, ?string $regexp): void {
        $path = $this->app->make(Repository::class)->get('lighthouse.schema.register');
        $path = dirname($path);

        if (!$regexp) {
            $name   = preg_quote($directive, '/');
            $regexp = "/(^|\s+){$name}($|\s+)/ui";
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

        self::assertEquals([], $usages, $replacement);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderForbiddenDirectives(): array {
        return [
            ['@guard', '@authMe', null],
            ['@orderBy', '@sortBy', null],
            ['@whereConditions', '@searchBy', null],
            ['@whereHasConditions', '@searchBy', null],
            ['@spread', null, null],
            ['@hash', null, null],
            ['@globalId', null, null],
            ['@trim', null, null],
            ['@rename', null, null],
            ['@paginate', '@paginated', null],
            ['@hasMany(type: xxx)', '@relation @paginatedRelation', '/@hasMany\(.*?type:.*?\)/ui'],
            ['@belongsToMany(type: xxx)', '@relation @paginatedRelation', '/@belongsToMany\(.*?type:.*?\)/ui'],
            ['@morphMany(type: xxx)', '@relation @paginatedRelation', '/@morphMany\(.*?type:.*?\)/ui'],
            ['@belongsTo', '@relation', null],
            ['@belongsToMany', '@relation', null],
            ['@hasOne', '@relation', null],
            ['@hasMany', '@relation', null],
            ['@morphMany', '@relation', null],
            ['@morphOne', '@relation', null],
            ['@morphTo', '@relation', null],
            ['@morphToMany', '@relation', null],
            ['@paginatedLimit', null, null],
            ['@paginatedOffset', null, null],
            ['@cache', '@cached', null],
            ['@cacheKey', '@cached', null],
        ];
    }
    //</editor-fold>
}
