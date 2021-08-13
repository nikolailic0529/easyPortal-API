<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Audit\Auditor;
use App\Services\Logger\Logger;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\WithFaker;
use LastDragon_ru\LaraASP\Testing\Database\RefreshDatabaseIfEmpty;
use LastDragon_ru\LaraASP\Testing\TestCase as BaseTestCase;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\GraphQL\ASTBuilderPersistent;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    use RefreshDatabaseIfEmpty;
    use MakesGraphQLRequests;
    use WithTranslations;
    use WithSettings;
    use WithOrganization;
    use WithUser;
    use WithFaker {
        faker as public;
    }
    use Override;
    use FakeDisks;

    /**
     * @var array<string>
     */
    protected array $connectionsToTransact = [
        'mysql',
        Logger::CONNECTION,
        Auditor::CONNECTION,
    ];

    public function app(): Application {
        return $this->app;
    }

    protected function setUp(): void {
        // Parent
        parent::setUp();

        // We cache AST for all tests because AST generation takes ~80% of the time.
        $this->app->singleton(ASTBuilder::class, function (): ASTBuilder {
            return $this->app->make(ASTBuilderPersistent::class);
        });
    }
}
