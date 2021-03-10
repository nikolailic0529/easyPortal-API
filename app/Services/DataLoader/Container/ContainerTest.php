<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Container;

use App\Services\DataLoader\Client;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Provider;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Container\Container
 */
class ContainerTest extends TestCase {
    /**
     * @covers ::resolve
     */
    public function testResolveExternal(): void {
        $this->assertNotNull($this->app->make(Container::class)->resolve('config'));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveProvider(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ProviderContainerTest_Provider::class);
        $b = $c->resolve(ProviderContainerTest_Provider::class);
        $s = $this->app->make(ProviderContainerTest_Provider::class);

        $this->assertNotNull($a);
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $s);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveFactory(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ProviderContainerTest_Singleton::class);
        $f = $c->resolve(ProviderContainerTest_Factory::class);
        $s = $this->app->make(ProviderContainerTest_Provider::class);

        $this->assertNotNull($a);
        $this->assertNotNull($f);
        $this->assertSame($a, $f->singleton);
        $this->assertNotSame($s, $f->singleton);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveLoader(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ProviderContainerTest_Singleton::class);
        $l = $c->resolve(ProviderContainerTest_Loader::class);
        $s = $this->app->make(ProviderContainerTest_Provider::class);

        $this->assertNotNull($a);
        $this->assertNotNull($l);
        $this->assertSame($a, $l->singleton);
        $this->assertNotSame($s, $l->singleton);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveSingleton(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ProviderContainerTest_Singleton::class);
        $b = $c->resolve(ProviderContainerTest_Singleton::class);
        $s = $this->app->make(ProviderContainerTest_Singleton::class);

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $s);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProviderContainerTest_Provider extends Provider {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProviderContainerTest_Factory extends Factory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        public ProviderContainerTest_Singleton $singleton,
    ) {
        parent::__construct($logger, $normalizer);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProviderContainerTest_Loader extends Loader {
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        public ProviderContainerTest_Singleton $singleton,
    ) {
        parent::__construct($logger, $client);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProviderContainerTest_Singleton implements Singleton {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProviderContainerTest_Isolated implements Isolated {
    // empty
}
