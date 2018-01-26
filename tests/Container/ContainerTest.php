<?php
namespace Awful\Container;

use Awful\AwfulTestCase;
use Awful\Container\Exceptions\AlreadyRegisteredException;
use Awful\Container\Exceptions\CircularDependencyException;

class ContainerTest extends AwfulTestCase
{
    public function testGetSelf()
    {
        $container = new Container();

        $this->assertSame($container, $container->get(Container::class));
    }

    public function testSingleton()
    {
        $container = new Container();

        $this->assertSame($container->get(Foo::class), $container->get(Foo::class));
    }

    public function testConstructorInjection()
    {
        $container = new Container();

        // NOTE: Bar and Baz depend circularly on one another, but this should
        // work just fine since Baz uses ChainedDependencies.
        $bar = $container->get(Bar::class);

        $foo = $container->get(Foo::class);
        $baz = $container->get(Baz::class);

        $this->assertSame($foo, $bar->foo);
        $this->assertSame($baz, $bar->baz);
    }

    public function testChainedInjection()
    {
        $container = new Container();

        $baz_child = $container->get(BazChild::class);

        $foo = $container->get(Foo::class);
        $bar = $container->get(Bar::class);

        $this->assertSame($foo, $baz_child->foo);
        $this->assertSame($bar, $baz_child->bar);
    }

    public function testCircularDependency()
    {
        $container = new Container();

        $this->expectException(CircularDependencyException::class);
        $container->get(Circular1::class);
    }

    public function testAlias()
    {
        $container = new Container();

        $container->alias(Bar::class, 'alias1', 'alias2');
        $bar = $container->get(Bar::class);
        $this->assertSame($bar, $container->get('alias1'));
        $this->assertSame($bar, $container->get('alias2'));
    }

    public function testRegister()
    {
        $container = new Container();

        $foo = new Foo();
        $container->register($foo, 'alias1', 'alias2');
        $this->assertSame($foo, $container->get(Foo::class));
        $this->assertSame($foo, $container->get('alias1'));
        $this->assertSame($foo, $container->get('alias2'));

        $bar = $container->get(Bar::class);
        $this->expectException(AlreadyRegisteredException::class);
        $container->register($bar);
    }

    public function testCall()
    {
        $container = new Container();

        $injected_bar = $container->call(function (Bar $injected_bar) {
            return $injected_bar;
        });
        $bar = $container->get(Bar::class);
        $this->assertSame($bar, $injected_bar);

        $extra_arg = 'foo';
        $ret = $container->call(function (Bar $injected_bar, $extra) {
            return $extra;
        }, $extra_arg);
        $this->assertSame($extra_arg, $ret);
    }
}

// Sample classes for injection

class Foo
{
}

class Bar
{
    // NOTE: Circular dependency works with ChainedDependencies.
    public function __construct(Foo $foo, Baz $baz)
    {
        $this->foo = $foo;
        $this->baz = $baz;
    }
}

class Baz
{
    use ChainedDependencies;

    const DEPENDENCIES = [
        'foo' => Foo::class,
    ];
}

class BazChild extends Baz
{
    use ChainedDependencies;

    const DEPENDENCIES = [
        'foo' => Bar::class, // This should be ignored
        'bar' => Bar::class,
    ];
}

class Circular1
{
    public function __construct(Circular2 $c)
    {
    }
}

class Circular2
{
    public function __construct(Circular1 $c)
    {
    }
}
