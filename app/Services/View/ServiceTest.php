<?php declare(strict_types = 1);

namespace App\Services\View;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

use function preg_quote;

/**
 * @internal
 * @covers \App\Services\View\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @coversNothing
     *
     * @dataProvider dataProviderForbiddenExpressions
     */
    public function testForbiddenExpressions(string $expression, ?string $replacement, ?string $regexp): void {
        $path = $this->app->viewPath();

        if (!$regexp) {
            $name   = preg_quote($expression, '/');
            $regexp = "/(^|\s+){$name}(^|\s+)/ui";
        }

        $finder = Finder::create()->in($path)->notPath('/^vendor/')->files()->contains($regexp)->sortByName();
        $usages = [];

        foreach ($finder as $file) {
            $usages[$file->getPathname()] = true;
        }

        if ($replacement) {
            $replacement = "Expression {$expression} is forbidden, {$replacement} should be used instead.";
        } else {
            $replacement = "Expression {$expression} is forbidden.";
        }

        self::assertEquals([], $usages, $replacement);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderForbiddenExpressions(): array {
        return [
            ['{!!', '@html', null],
            ['@php', null, null],
        ];
    }
    //</editor-fold>
}
