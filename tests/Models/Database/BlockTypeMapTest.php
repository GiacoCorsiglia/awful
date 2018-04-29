<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\BlockTypeMapTest\Cls1;
use Awful\Models\Database\BlockTypeMapTest\Cls2;
use Awful\Models\Database\BlockTypeMapTest\Cls3;
use Awful\Models\Database\Exceptions\DuplicateBlockTypeException;
use Awful\Models\Database\Exceptions\UnknownBlockTypeException;
use Awful\Models\Database\Exceptions\UnregisteredBlockClassException;

class BlockTypeMapTest extends AwfulTestCase
{
    /** @var BlockTypeMap */
    private $map;

    public function setUp()
    {
        parent::setUp();

        $this->map = new BlockTypeMap([
            Cls1::class => 'type1',
            Cls2::class => ['type2'],
            Cls3::class => ['type3', 'type4'],
        ]);
    }

    public function testClassForType()
    {
        $this->assertSame(Cls1::class, $this->map->classForType('type1'));
        $this->assertSame(Cls2::class, $this->map->classForType('type2'));
        $this->assertSame(Cls3::class, $this->map->classForType('type3'));
        $this->assertSame(Cls3::class, $this->map->classForType('type3'));
    }

    public function testClassForTypeUnknown()
    {
        $this->expectException(UnknownBlockTypeException::class);
        $this->map->classForType('some unknown type');
    }

    public function testTypeForClass()
    {
        $this->assertSame('type1', $this->map->typeForClass(Cls1::class));
        $this->assertSame('type2', $this->map->typeForClass(Cls2::class));
        $this->assertSame('type3', $this->map->typeForClass(Cls3::class));
    }

    public function testTypeForClassUnregistered()
    {
        $this->expectException(UnregisteredBlockClassException::class);
        $this->map->typeForClass('some unknown class');
    }

    public function testConstructorRejectsDuplicateType()
    {
        $this->expectException(DuplicateBlockTypeException::class);
        new BlockTypeMap([
            Cls1::class => 'type1',
            Cls2::class => ['type2', 'type1'],
        ]);
    }
}
namespace Awful\Models\Database\BlockTypeMapTest;

use Awful\Models\Block;

class Cls1 extends Block
{
}

class Cls2 extends Block
{
}

class Cls3 extends Block
{
}
