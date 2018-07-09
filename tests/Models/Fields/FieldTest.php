<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Fields\FieldTest\TestField;

class FieldTest extends AwfulTestCase
{
    public function testIsRequired()
    {
        $this->assertFalse((new TestField([]))->isRequired());
        $this->assertFalse((new TestField(['required' => false]))->isRequired());
        $this->assertTrue((new TestField(['required' => true]))->isRequired());
    }

    public function testJsonEncode()
    {
        $field = new TestField([
            'foo' => 'bar',
            'required' => true,
        ]);

        $this->assertSame([
            '$type' => 'Awful.Models.Fields.FieldTest.TestField',
            'foo' => 'bar',
            'required' => true,
            // These come from Field::DEFAULTS
            'default_value' => null,
            'instructions' => '',
            'label' => '',
        ], $field->jsonSerialize());

        $this->assertSame(
            '{"$type":"Awful.Models.Fields.FieldTest.TestField","foo":"bar","required":true,"default_value":null,"instructions":"","label":""}',
            json_encode($field)
        );
    }
}
namespace Awful\Models\Fields\FieldTest;

use Awful\Models\Fields\Field;
use Awful\Models\Model;

class TestField extends Field
{
    public function clean($value, Model $model)
    {
        return $value;
    }

    public function toPhp($value, Model $model, string $fieldKey)
    {
        return $value;
    }
}
