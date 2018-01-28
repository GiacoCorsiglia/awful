<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\HasFields;

class TextFieldTest extends AwfulTestCase
{
    public function testToAcf()
    {
        $field = new TextField(['foo' => 'bar']);

        $this->assertArraySubset([
            'type' => 'text',
            'key' => 'field_name',
            'name' => 'name',
            'foo' => 'bar',
        ], $field->toAcf('name', '', new FieldsResolver($this->container())));
    }

    public function testForPhp()
    {
        $field = new TextField();

        $owner = $this->getMockForAbstractClass(HasFields::class);

        $this->assertSame('', $field->forPhp('', $owner, ''));
        $this->assertSame('foo', $field->forPhp('foo', $owner, ''));
        $this->assertSame('', $field->forPhp(false, $owner, ''));
        $this->assertSame('', $field->forPhp(null, $owner, ''));
        $this->assertSame('1', $field->forPhp(true, $owner, ''));
        $this->assertSame('', $field->forPhp(['foo'], $owner, ''));
    }
}
