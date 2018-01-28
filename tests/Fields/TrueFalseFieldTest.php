<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\HasFields;

class TrueFalseFieldTest extends AwfulTestCase
{
    public function testToAcf()
    {
        $field = new TrueFalseField(['foo' => 'bar']);

        $this->assertArraySubset([
            'type' => 'true_false',
            'key' => 'field_name',
            'name' => 'name',
            'foo' => 'bar',
        ], $field->toAcf('name', '', new FieldsResolver($this->container())));
    }

    public function testForPhp()
    {
        $field = new TrueFalseField();
        $owner = $this->getMockForAbstractClass(HasFields::class);

        $this->assertSame(false, $field->forPhp(false, $owner, ''));
        $this->assertSame(false, $field->forPhp(null, $owner, ''));
        $this->assertSame(false, $field->forPhp(0, $owner, ''));
        $this->assertSame(false, $field->forPhp('', $owner, ''));
        $this->assertSame(false, $field->forPhp([], $owner, ''));

        $this->assertSame(true, $field->forPhp(true, $owner, ''));
        $this->assertSame(true, $field->forPhp(1, $owner, ''));
        $this->assertSame(true, $field->forPhp('foo', $owner, ''));
        $this->assertSame(true, $field->forPhp(['foo'], $owner, ''));
        $this->assertSame(true, $field->forPhp(new \stdClass(), $owner, ''));
    }
}
