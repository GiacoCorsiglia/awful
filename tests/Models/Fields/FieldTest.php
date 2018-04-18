<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\BlockOwnerModel;
use Awful\Models\Exceptions\ValidationException;

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

    public function testToAcf()
    {
        $field = $this->getMockForAbstractClass(Field::class, [[
            'foo' => 'bar',
        ]]);

        $res_class = new \ReflectionClass(FieldsResolver::class);
        $resolver = $res_class->newInstanceWithoutConstructor();

        // Use assertEquals to ignore order of keys.
        $this->assertEquals([
            'key' => 'field_name',
            'name' => 'name',
            'type' => '', // Field::ACF_TYPE
            'foo' => 'bar',
        ], $field->toAcf('name', '', $resolver));

        // TODO: Test hook registration.

        $this->assertEquals([
            'key' => 'field_base__name',
            'name' => 'name',
            'type' => '', // Field::ACF_TYPE
            'foo' => 'bar',
        ], $field->toAcf('name', 'base', $resolver));
    }

    public function testAcfLoadValueFilter()
    {
        $field = $this->getMockForAbstractClass(Field::class);

        $this->assertSame('foo', $field->acfLoadValueFilter('foo', 1, []));
    }

    public function testAcfValidateValueFilter()
    {
        $field = $this->getMockForAbstractClass(Field::class);

        $this->assertSame(true, $field->acfValidateValueFilter(true, '', [], ''), 'Preserves validity when valid');
        $this->assertSame(false, $field->acfValidateValueFilter(false, '', [], ''), 'Preserves invalidity when invalid');
        $this->assertSame('invalid', $field->acfValidateValueFilter('invalid', '', [], ''), 'Preserves invalidity when invalid with reason');

        $field_with_clean = new class() extends Field {
            public function forPhp($value, BlockOwnerModel $owner, string $field_name)
            {
                // Required abstract method
            }

            public function clean($value)
            {
                throw new ValidationException('exception');
            }
        };

        $this->assertSame(false, $field->acfValidateValueFilter(false, '', [], ''), 'Preserves invalidity when invalid');
        $this->assertSame('invalid', $field->acfValidateValueFilter('invalid', '', [], 'Preserves invalidity when invalid with reason'));
        $this->assertSame('exception', $field->acfValidateValueFilter('exception', '', [], ''), 'Converts ValidationException to string for ACF');
    }

    public function testAcfUpdateValueFilter()
    {
        $field = $this->getMockForAbstractClass(Field::class);

        $this->assertSame('foo', $field->acfUpdateValueFilter('foo', 1, []));
    }
}
