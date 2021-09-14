<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Testings\DotenvRepository;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Config\Repository as RepositoryImpl;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Foundation\Application as ApplicationImpl;
use Illuminate\Support\Env;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempFile;
use Mockery;
use Tests\TestCase;

use function basename;
use function dirname;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Environment
 */
class EnvironmentTest extends TestCase {
    use WithTempFile;

    /**
     * @covers ::has
     */
    public function testGet(): void {
        $repository = new DotenvRepository(['TEST' => null, 'TEST2' => 123]);
        $env        = Mockery::mock(Environment::class);
        $env->shouldAllowMockingProtectedMethods();
        $env->makePartial();
        $env
            ->shouldReceive('getRepository')
            ->times(3)
            ->andReturn($repository);

        $this->assertNull($env->get('TEST'));
        $this->assertNull($env->get('UNKNOWN'));
        $this->assertEquals(123, $env->get('TEST2'));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $repository = new DotenvRepository(['TEST' => null]);
        $env        = Mockery::mock(Environment::class);
        $env->shouldAllowMockingProtectedMethods();
        $env->makePartial();
        $env
            ->shouldReceive('getRepository')
            ->twice()
            ->andReturn($repository);

        $this->assertTrue($env->has('TEST'));
        $this->assertFalse($env->has('UNKNOWN'));
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryNotCachesConfiguration(): void {
        $app         = Mockery::mock(Application::class);
        $config      = Mockery::mock(Repository::class);
        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };

        $this->assertSame(Env::getRepository(), $environment->getRepository());
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryCachesConfiguration(): void {
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

        $this->assertSame(Env::getRepository(), $environment->getRepository());
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryCachedConfigWithEnvFile(): void {
        $env    = $this->getTempFile('TEST=value')->getPathname();
        $config = new RepositoryImpl();

        $config->set(Settings::ENV_PATH, $env);

        $app = Mockery::mock(Application::class, CachesConfiguration::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);
        $app
            ->shouldReceive('environment')
            ->once()
            ->andReturn('unknown');
        $app
            ->shouldReceive('basePath')
            ->with('.unknown')
            ->once()
            ->andReturn('unknown');

        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };
        $repository  = $environment->getRepository();

        $this->assertNotSame(Env::getRepository(), $repository);
        $this->assertTrue($environment->has('TEST'));
        $this->assertEquals('value', $environment->get('TEST'));
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryCachedConfigWithoutEnvFile(): void {
        $env    = $this->getTempFile('TEST=value')->getPathname();
        $config = new RepositoryImpl();
        $app    = Mockery::mock(Application::class, CachesConfiguration::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);
        $app
            ->shouldReceive('environment')
            ->once()
            ->andReturn(basename($env));
        $app
            ->shouldReceive('basePath')
            ->with('.'.basename($env))
            ->once()
            ->andReturn($env);

        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };
        $repository  = $environment->getRepository();

        $this->assertNotSame(Env::getRepository(), $repository);
        $this->assertTrue($environment->has('TEST'));
        $this->assertEquals('value', $environment->get('TEST'));
    }

    /**
     * @covers ::getRepository
     */
    public function testGetRepositoryCachedConfigWithoutEnvFileApplicationImpl(): void {
        $env    = $this->getTempFile('TEST=value')->getPathname();
        $config = new RepositoryImpl();
        $app    = Mockery::mock(ApplicationImpl::class);
        $app
            ->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);
        $app
            ->shouldReceive('environment')
            ->once()
            ->andReturn('unknown');
        $app
            ->shouldReceive('basePath')
            ->never();
        $app
            ->shouldReceive('environmentPath')
            ->once()
            ->andReturn(dirname($env));
        $app
            ->shouldReceive('environmentFilePath')
            ->once()
            ->andReturn($env);

        $environment = new class ($app, $config) extends Environment {
            public function getRepository(): RepositoryInterface {
                return parent::getRepository();
            }
        };
        $repository  = $environment->getRepository();

        $this->assertNotSame(Env::getRepository(), $repository);
        $this->assertTrue($environment->has('TEST'));
        $this->assertEquals('value', $environment->get('TEST'));
    }
}
