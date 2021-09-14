<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
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
        $env = Mockery::mock(Environment::class);
        $env->makePartial();
        $env
            ->shouldReceive('load')
            ->times(3)
            ->andReturn(['TEST' => null, 'TEST2' => 123]);

        $this->assertNull($env->get('TEST'));
        $this->assertNull($env->get('UNKNOWN'));
        $this->assertEquals(123, $env->get('TEST2'));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $env = Mockery::mock(Environment::class);
        $env->makePartial();
        $env
            ->shouldReceive('load')
            ->twice()
            ->andReturn(['TEST' => null]);

        $this->assertTrue($env->has('TEST'));
        $this->assertFalse($env->has('UNKNOWN'));
    }

    /**
     * @covers ::load
     */
    public function testLoad(): void {
        $env = $this->getTempFile('TEST=value')->getPathname();
        $app = Mockery::mock(Application::class);
        $app
            ->shouldReceive('environmentPath')
            ->once()
            ->andReturn(dirname($env));
        $app
            ->shouldReceive('environmentFile')
            ->once()
            ->andReturn(basename($env));

        $environment = new Environment($app);

        $this->assertEquals(['TEST' => 'value'], $environment->load());
        $this->assertEquals(['TEST' => 'value'], $environment->load());
    }
}
