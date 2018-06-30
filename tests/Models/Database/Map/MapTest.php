<?php
namespace Awful\Models\Database\Map;

use Awful\AwfulTestCase;
use Awful\Models\Database\Map\Exceptions\DuplicateTypeException;
use Awful\Models\Database\Map\Exceptions\UnknownTypeException;
use Awful\Models\Database\Map\Exceptions\UnregisteredClassException;

/**
 * This serves as a test for all the instances of `AbstractTypeMap`.
 */
class MapTest extends AwfulTestCase
{
    /** @var AbstractTypeMap */
    private $map;

    public function setUp()
    {
        parent::setUp();

        $map = [
            MapTest\Cls1::class => 'type1',
            MapTest\Cls2::class => ['type2'],
            MapTest\Cls3::class => ['type3', 'type4'],
        ];

        $this->map = new class($map) extends AbstractTypeMap {
        };
    }

    public function testClassForType()
    {
        $this->assertSame(MapTest\Cls1::class, $this->map->classForType('type1'));
        $this->assertSame(MapTest\Cls2::class, $this->map->classForType('type2'));
        $this->assertSame(MapTest\Cls3::class, $this->map->classForType('type3'));
        $this->assertSame(MapTest\Cls3::class, $this->map->classForType('type3'));
    }

    public function testClassForTypeUnknown()
    {
        $this->expectException(UnknownTypeException::class);
        $this->map->classForType('some unknown type');
    }

    public function testTypeForClass()
    {
        $this->assertSame('type1', $this->map->typeForClass(MapTest\Cls1::class));
        $this->assertSame('type2', $this->map->typeForClass(MapTest\Cls2::class));
        $this->assertSame('type3', $this->map->typeForClass(MapTest\Cls3::class));
    }

    public function testTypeForClassUnregistered()
    {
        $this->expectException(UnregisteredClassException::class);
        $this->map->typeForClass('some unknown class');
    }

    public function testConstructorRejectsDuplicateType()
    {
        $this->expectException(DuplicateTypeException::class);

        $map = [
            MapTest\Cls1::class => 'type1',
            MapTest\Cls2::class => ['type2', 'type1'],
        ];

        new class($map) extends AbstractTypeMap {
        };
    }
}

// Test classes
namespace Awful\Models\Database\Map\MapTest;

class Cls1
{
}

class Cls2
{
}

class Cls3
{
}
