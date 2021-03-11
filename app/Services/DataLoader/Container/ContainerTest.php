<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Container;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolver;
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
        $a = $c->resolve(ContainerTest_Resolver::class);
        $b = $c->resolve(ContainerTest_Resolver::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

        $this->assertNotNull($a);
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $s);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveFactory(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $f = $c->resolve(ContainerTest_Factory::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

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
        $a = $c->resolve(ContainerTest_Singleton::class);
        $l = $c->resolve(ContainerTest_Loader::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

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
        $a = $c->resolve(ContainerTest_Singleton::class);
        $b = $c->resolve(ContainerTest_Singleton::class);
        $s = $this->app->make(ContainerTest_Singleton::class);

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
class ContainerTest_Resolver extends Resolver {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ContainerTest_Factory extends Factory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        public ContainerTest_Singleton $singleton,
    ) {
        parent::__construct($logger, $normalizer);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ContainerTest_Loader extends Loader {
    public function __construct(
        LoggerInterface $logger,
        Client $client,
        public ContainerTest_Singleton $singleton,
    ) {
        parent::__construct($logger, $client);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ContainerTest_Singleton implements Singleton {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ContainerTest_Isolated implements Isolated {
    // empty
}
