<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Post;
use Awful\Models\SubModel;

class RepeaterFieldTest extends AwfulTestCase
{
    private function emptyRowClass()
    {
        return get_class(new class() extends SubModel {
        });
    }

    private function rowClass()
    {
        return get_class(new class() extends SubModel {
            public static function getFields()
            {
                return [
                    'text_field' => new TextField(),
                ];
            }
        });
    }

    public function testToAcfWithEmptyRowClass()
    {
        $field = new RepeaterField([
            'foo' => 'bar',
            'row_class' => $this->emptyRowClass(),
        ]);

        $this->assertArraySubset([
            'type' => 'repeater',
            'key' => 'field_name',
            'name' => 'name',
            'foo' => 'bar',
            'sub_fields' => [],
        ], $field->toAcf('name', '', new FieldsResolver($this->container())));
    }

    public function testToAcfWithRowClass()
    {
        $field = new RepeaterField([
            'foo' => 'bar',
            'row_class' => $this->rowClass(),
            'collapsed' => 'text_field',
        ]);

        $this->assertArraySubset([
            'type' => 'repeater',
            'key' => 'field_name',
            'name' => 'name',
            'foo' => 'bar',
            'collapsed' => 'field_name__text_field',
            'sub_fields' => [
                [
                    'type' => 'text',
                    'name' => 'text_field',
                    'key' => 'field_name__text_field',
                ],
            ],
        ], $field->toAcf('name', '', new FieldsResolver($this->container())));
    }

    public function testForPhpWithModel()
    {
        $row_class = $this->rowClass();
        $field = new RepeaterField([
            'row_class' => $row_class,
        ]);

        $owner = $this->getMockBuilder(Post::class)
            ->disableOriginalConstructor()
            ->getMock();

        $owner->expects($this->any())
            ->method('getRawFieldValue')
            ->willReturnMap([
                ['repeater_name_0_text_field', 'first buz'],
                ['repeater_name_1_text_field', 'second buz'],
            ]);

        $owner->expects($this->any())
            ->method('getFieldsResolver')
            ->willReturn(new FieldsResolver($this->container()));

        $this->assertSame([], $field->forPhp(null, $owner, ''));
        $this->assertSame([], $field->forPhp(false, $owner, ''));
        $this->assertSame([], $field->forPhp(0, $owner, ''));
        $this->assertSame([], $field->forPhp('0', $owner, ''));

        $rows = $field->forPhp(2, $owner, 'repeater_name');
        $this->assertSame(2, count($rows));
        $this->assertTrue($rows[0] instanceof $row_class);
        $this->assertTrue($rows[1] instanceof $row_class);
        $this->assertSame('first buz', $rows[0]->get('text_field'));
        $this->assertSame('second buz', $rows[1]->get('text_field'));
        $this->assertSame(null, $rows[1]->get('some random field'));

        $rows = $field->forPhp('2', $owner, 'repeater_name');
        $this->assertSame(2, count($rows));
    }
}
