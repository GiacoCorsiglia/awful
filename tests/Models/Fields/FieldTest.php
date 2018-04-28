<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\BlockOwnerModel;

class FieldTest extends AwfulTestCase
{
    public function testForPhp()
    {
        $field = $this->getMockForAbstractClass(Field::class);

        $field->expects($this->any())
            ->method('forPhp')
            ->willReturnArgument(0);

        $owner = $this->getMockForAbstractClass(BlockOwnerModel::class);

        $this->assertSame('foo', $field->forPhp('foo', $owner, 'field_name'));
    }

    public function testForEditor()
    {
        $field = $this->getMockForAbstractClass(Field::class);

        $this->assertSame('foo', $field->forEditor('foo'));
    }

    public function testClean()
    {
        $field = $this->getMockForAbstractClass(Field::class);

        $this->assertSame('foo', $field->clean('foo'));
    }
}
