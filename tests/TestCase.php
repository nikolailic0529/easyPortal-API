<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Audit\Auditor;
use App\Services\Logger\Logger;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Settings\Storage;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\GlobalScopes\State;
use Closure;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Testing\Database\RefreshDatabaseIfEmpty;
use LastDragon_ru\LaraASP\Testing\TestCase as BaseTestCase;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempFile;
use Nuwave\Lighthouse\Schema\AST\ASTBuilder;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\GraphQL\ASTBuilderPersistent;
use Tests\Helpers\SequenceDateFactory;
use Tests\Helpers\SequenceUuidFactory;

use function array_shift;
use function file_put_contents;
use function pathinfo;

use const PATHINFO_EXTENSION;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    use RefreshDatabaseIfEmpty {
        RefreshDatabaseIfEmpty::refreshTestDatabase as protected laraaspRefreshTestDatabase;
    }
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
    use WithTempFile;
    use WithEvents;

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

        // Some tests may change MorphMap, we need to reset it
        $this->beforeApplicationDestroyed(static function (): void {
            Relation::$morphMap = [];
        });

        // Some tests may change GlobalScopes, we need to reset it
        $this->beforeApplicationDestroyed(static function (): void {
            State::reset();
        });

        // Some tests may use custom UUIDs, we need to reset it
        $this->beforeApplicationDestroyed(static function (): void {
            Str::createUuidsNormally();
        });

        // We cache AST for all tests because AST generation takes ~80% of the time.
        $this->afterApplicationCreated(function (): void {
            $this->app->singleton(ASTBuilder::class, function (): ASTBuilder {
                return $this->app->make(ASTBuilderPersistent::class);
            });
        });

        // Custom Settings should not be used/changed
        $this->afterApplicationCreated(function (): void {
            $this->app->bind(Storage::class, function (): Storage {
                return new class($this->app, $this->getTempFile('{}')->getPathname()) extends Storage {
                    public function __construct(
                        Application $app,
                        protected string $path,
                    ) {
                        parent::__construct($app);
                    }

                    protected function getPath(): string {
                        return $this->path;
                    }
                };
            });
        });
    }

    protected function refreshTestDatabase(): void {
        // All connections should use a test database (this is especially
        // important for parallel testing), so we need to update them.
        //
        // PS: Probably it should not be here?
        $connections = $this->connectionsToTransact();
        $default     = array_shift($connections);

        if ($default && $connections) {
            $db       = $this->app->make('db');
            $config   = $this->app->make(Repository::class);
            $database = $config->get("database.connections.{$default}.database");

            foreach ($connections as $connection) {
                $setting = "database.connections.{$connection}.database";

                if ($config->get($setting) !== $database) {
                    $config->set($setting, $database);
                    $db->purge($connection);
                }
            }
        }

        $this->laraaspRefreshTestDatabase();
    }

    /**
     * @param array<class-string<Model>,int> $expected
     */
    protected function assertModelsCount(array $expected): void {
        $actual = [];

        foreach ($expected as $model => $count) {
            $actual[$model] = GlobalScopes::callWithoutGlobalScope(
                OwnedByOrganizationScope::class,
                static function () use ($model): int {
                    return $model::query()->count();
                },
            );
        }

        $this->assertEquals($expected, $actual);
    }

    protected function assertCommandDescription(string $command, string $expected = '.txt'): void {
        $buffer = new BufferedOutput();
        $kernel = $this->app->make(Kernel::class);
        $format = pathinfo($expected, PATHINFO_EXTENSION);
        $result = $kernel->call('help', ['command_name' => $command, '--format' => $format], $buffer);
        $actual = $buffer->fetch();
        $data   = $this->getTestData();

        if ($data->content($expected) === '') {
            $this->assertNotFalse(file_put_contents($data->path($expected), $actual));
        }

        $this->assertEquals(Command::SUCCESS, $result);
        $this->assertEquals($data->content($expected), $actual);
    }

    protected function overrideUuidFactory(string $seed): void {
        Str::createUuidsUsing(new SequenceUuidFactory($seed));
    }

    protected function resetUuidFactory(): void {
        Str::createUuidsNormally();
    }

    protected function overrideDateFactory(DateTimeInterface|string $now): void {
        Date::setTestNow(Closure::fromCallable(new SequenceDateFactory($now)));
    }

    protected function resetDateFactory(): void {
        Date::setTestNow();
    }
}
