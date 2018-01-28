<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\BlogPost;
use Awful\Models\SubModel;

class FlexibleContentFieldTest extends AwfulTestCase
{
    private function emptyLayoutClass()
    {
        return get_class(new class() extends SubModel {
            const LABEL = 'label';

            const DISPLAY = 'display';
        });
    }

    private function layoutClass()
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

    public function testToAcf()
    {
        $field = new FlexibleContentField([
            'foo' => 'bar',
            'layout_classes' => [
                'empty' => $this->emptyLayoutClass(),
                'layout' => $this->layoutClass(),
            ],
        ]);

        $this->assertArraySubset([
            'type' => 'flexible_content',
            'key' => 'field_name',
            'name' => 'name',
            'foo' => 'bar',
            'layouts' => [
                [
                    'key' => 'name__empty',
                    'name' => 'empty',
                    'label' => 'label',
                    'display' => 'display',
                    'sub_fields' => [],
                ],
                [
                    'key' => 'name__layout',
                    'name' => 'layout',
                    'label' => '',
                    'display' => '',
                    'sub_fields' => [
                        [
                            'key' => 'field_name__layout__text_field',
                            'name' => 'text_field',
                        ],
                    ],
                ],
            ],
        ], $field->toAcf('name', '', new FieldsResolver($this->container())));
    }

    public function testForPhpWithModel()
    {
        $layout_class_A = $this->layoutClass();
        $layout_class_B = $this->layoutClass();
        $field = new FlexibleContentField([
            'layout_classes' => [
                'layoutA' => $layout_class_A,
                'layoutB' => $layout_class_A,
            ],
        ]);

        $owner = $this->getMockBuilder(BlogPost::class)
            ->disableOriginalConstructor()
            ->getMock();

        $owner->expects($this->any())
            ->method('getRaw')
            ->willReturnMap([
                ['flexible_content_name_0_text_field', 'first buz'],
                ['flexible_content_name_1_text_field', 'second buz'],
            ]);

        $owner->expects($this->any())
            ->method('getFieldsResolver')
            ->willReturn(new FieldsResolver($this->container()));

        $this->assertSame([], $field->forPhp(null, $owner, ''));
        $this->assertSame([], $field->forPhp(false, $owner, ''));
        $this->assertSame([], $field->forPhp(0, $owner, ''));
        $this->assertSame([], $field->forPhp('0', $owner, ''));
        $this->assertSame([], $field->forPhp(1, $owner, ''));
        $this->assertSame([], $field->forPhp([], $owner, ''));

        $layouts = $field->forPhp(['layoutB', 'layoutA'], $owner, 'flexible_content_name');
        $this->assertSame(2, count($layouts));
        $this->assertTrue($layouts[0] instanceof $layout_class_B);
        $this->assertTrue($layouts[1] instanceof $layout_class_A);
        $this->assertSame('first buz', $layouts[0]->get('text_field'));
        $this->assertSame('second buz', $layouts[1]->get('text_field'));
        $this->assertSame(null, $layouts[1]->get('some random field'));
    }
}
