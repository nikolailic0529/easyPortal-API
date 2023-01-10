<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Container;

use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;
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
        self::assertNotNull($this->app->make(Container::class)->resolve('config'));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveProvider(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Resolver::class);
        $b = $c->resolve(ContainerTest_Resolver::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

        self::assertNotNull($a);
        self::assertSame($a, $b);
        self::assertNotSame($a, $s);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveFactory(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $f = $c->resolve(ContainerTest_Factory::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

        self::assertNotNull($a);
        self::assertNotNull($f);
        self::assertSame($a, $f->singleton);
        self::assertNotSame($s, $f->singleton);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveSingleton(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $b = $c->resolve(ContainerTest_Singleton::class);
        $s = $this->app->make(ContainerTest_Singleton::class);

        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame($a, $b);
        self::assertNotSame($a, $s);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveSelf(): void {
        // Container should be a singleton too
        $c = $this->app->make(Container::class);
        $a = $c->resolve(Container::class);
        $b = $c->resolve(Container::class);

        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame($a, $b);
        self::assertSame($a, $c);

        // But only inside themself
        $d = $this->app->make(Container::class);
        $e = $d->resolve(Container::class);

        self::assertSame($d, $e);
        self::assertNotSame($d, $c);
        self::assertNotSame($e, $a);
    }

    /**
     * @covers ::forgetInstances
     */
    public function testForgetInstances(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $b = $c->resolve(ContainerTest_SingletonPersistent::class);

        self::assertNotNull($a);
        self::assertNotNull($b);
        self::assertSame($a, $c->resolve(ContainerTest_Singleton::class));
        self::assertSame($b, $c->resolve(ContainerTest_SingletonPersistent::class));

        $c->forgetInstances();

        self::assertNotSame($a, $c->resolve(ContainerTest_Singleton::class));
        self::assertSame($b, $c->resolve(ContainerTest_SingletonPersistent::class));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends Resolver<Model>
 */
class ContainerTest_Resolver extends Resolver {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends Factory<Model>
 */
class ContainerTest_Factory extends Factory {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        public ContainerTest_Singleton $singleton,
    ) {
        parent::__construct($exceptionHandler);
    }

    public function create(Type $type, bool $force = false): ?Model {
        return null;
    }

    public function getModel(): string {
        return Model::class;
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
class ContainerTest_SingletonPersistent implements Singleton, SingletonPersistent {
    // empty
}
