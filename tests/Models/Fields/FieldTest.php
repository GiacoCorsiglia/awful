<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;

class FieldTest extends AwfulTestCase
{
    public function testIsRequired()
    {
        $this->assertFalse($this->mockField([])->isRequired());
        $this->assertFalse($this->mockField(['required' => false])->isRequired());
        $this->assertTrue($this->mockField(['required' => true])->isRequired());
    }

    private function mockField(array $args): Field
    {
        return new class($args) extends Field {
            public function toPhp($value, \Awful\Models\Model $model, string $fieldKey)
            {
                return $value;
            }

            public function clean($value, \Awful\Models\Model $model)
            {
                return $value;
            }
        };
    }
}
