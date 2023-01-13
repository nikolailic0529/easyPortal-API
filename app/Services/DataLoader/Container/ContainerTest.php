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
 * @covers \App\Services\DataLoader\Container\Container
 */
class ContainerTest extends TestCase {
    public function testResolveExternal(): void {
        self::assertNotEmpty($this->app->make(Container::class)->resolve('config'));
    }

    public function testResolveProvider(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Resolver::class);
        $b = $c->resolve(ContainerTest_Resolver::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

        self::assertNotEmpty($a);
        self::assertSame($a, $b);
        self::assertNotSame($a, $s);
    }

    public function testResolveFactory(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $f = $c->resolve(ContainerTest_Factory::class);
        $s = $this->app->make(ContainerTest_Resolver::class);

        self::assertNotEmpty($a);
        self::assertNotEmpty($f);
        self::assertSame($a, $f->singleton);
        self::assertNotSame($s, $f->singleton);
    }

    public function testResolveSingleton(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $b = $c->resolve(ContainerTest_Singleton::class);
        $s = $this->app->make(ContainerTest_Singleton::class);

        self::assertNotEmpty($a);
        self::assertNotEmpty($b);
        self::assertSame($a, $b);
        self::assertNotSame($a, $s);
    }

    public function testResolveSelf(): void {
        // Container should be a singleton too
        $c = $this->app->make(Container::class);
        $a = $c->resolve(Container::class);
        $b = $c->resolve(Container::class);

        self::assertNotEmpty($a);
        self::assertNotEmpty($b);
        self::assertSame($a, $b);
        self::assertSame($a, $c);

        // But only inside themself
        $d = $this->app->make(Container::class);
        $e = $d->resolve(Container::class);

        self::assertSame($d, $e);
        self::assertNotSame($d, $c);
        self::assertNotSame($e, $a);
    }

    public function testForgetInstances(): void {
        $c = $this->app->make(Container::class);
        $a = $c->resolve(ContainerTest_Singleton::class);
        $b = $c->resolve(ContainerTest_SingletonPersistent::class);

        self::assertNotEmpty($a);
        self::assertNotEmpty($b);
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
