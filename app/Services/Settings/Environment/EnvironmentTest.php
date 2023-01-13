<?php declare(strict_types = 1);

namespace App\Services\Settings\Environment;

use Dotenv\Repository\RepositoryInterface;
use Illuminate\Config\Repository as RepositoryImpl;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Env;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Settings\Environment\Environment
 */
class EnvironmentTest extends TestCase {
    public function testGet(): void {
        $repository = new EnvironmentRepository(['TEST' => null, 'TEST2' => 123]);
        $env        = Mockery::mock(Environment::class);
        $env->shouldAllowMockingProtectedMethods();
        $env->makePartial();
        $env
            ->shouldReceive('getRepository')
            ->times(3)
            ->andReturn($repository);

        self::assertNull($env->get('TEST'));
        self::assertNull($env->get('UNKNOWN'));
        self::assertEquals(123, $env->get('TEST2'));
    }

    public function testHas(): void {
        $repository = new EnvironmentRepository(['TEST' => null]);
        $env        = Mockery::mock(Environment::class);
        $env->shouldAllowMockingProtectedMethods();
        $env->makePartial();
        $env
            ->shouldReceive('getRepository')
            ->twice()
            ->andReturn($repository);

        self::assertTrue($env->has('TEST'));
        self::assertFalse($env->has('UNKNOWN'));
    }

    public function testGetRepositoryNotCachesConfiguration(): void {
        $app         = Mockery::mock(Application::class);
        $config      = Mockery::mock(Repository::class);
        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };

        self::assertSame(Env::getRepository(), $environment->getRepository());
    }

    public function testGetRepositoryCachesConfigurationNoCache(): void {
        $app = Mockery::mock(Application::class, CachesConfiguration::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(false);

        $config      = Mockery::mock(Repository::class);
        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };

        self::assertSame(Env::getRepository(), $environment->getRepository());
    }

    public function testGetRepositoryCachesConfigurationCache(): void {
        $config = new RepositoryImpl([
            Environment::SETTING => [
                'TEST' => 'value',
            ],
        ]);

        $app = Mockery::mock(Application::class, CachesConfiguration::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);

        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };
        $repository  = $environment->getRepository();

        self::assertNotSame(Env::getRepository(), $repository);
        self::assertTrue($environment->has('TEST'));
        self::assertEquals('value', $environment->get('TEST'));
    }
}
