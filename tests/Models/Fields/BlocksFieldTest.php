<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Database\BlockSet;
use Awful\Models\Database\EntityManager;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Fields\BlocksFieldTest\Block1;
use Awful\Models\Fields\BlocksFieldTest\Block2;
use Awful\Models\Site;
use Awful\Models\WordPressModel;

class BlocksFieldTest extends AwfulTestCase
{
    public function testCleanRejectsBlockWithDisallowedType()
    {
        $field = $this->instance([
            'types' => [
                Block1::class,
            ],
        ]);
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
                'type' => 'block_2',
            ],
        ]);

        $this->expectValidationException();
        $field->clean(['1'], $model);
    }

    public function testCleanRejectsBlockWithoutType()
    {
        $field = $this->instance();
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
            ],
        ]);

        $this->expectValidationException();
        $field->clean(['1'], $model);
    }

    public function testCleanRejectsBlockWithUnknownType()
    {
        $field = $this->instance();
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
                'type' => 'unknown',
            ],
        ]);

        $this->expectValidationException();
        $field->clean(['1'], $model);
    }

    public function testCleanRejectsNonArray()
    {
        $field = $this->instance();
        $model = $this->mockSite();

        $this->expectValidationException();
        $field->clean('', $model);
    }

    public function testCleanWithEmpty()
    {
        $field = $this->instance();
        $model = $this->mockSite();

        $this->assertSame([], $field->clean(null, $model));
        $this->assertSame([], $field->clean([], $model));
    }

    public function testCleanWithMax()
    {
        $field = $this->instance(['max' => 2]);
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
                'type' => 'block_1',
            ],
            '2' => [
                'uuid' => '2',
                'type' => 'block_1',
            ],
            '3' => [
                'uuid' => '3',
                'type' => 'block_1',
            ],
        ]);

        $this->assertSame(['1', '2'], $field->clean(['1', '2'], $model));
        $this->assertSame(['1'], $field->clean(['1'], $model));
        $this->assertSame([], $field->clean([], $model));

        $this->expectValidationException();
        $field->clean(['1', '2', '3'], $model);
    }

    public function testCleanWithMin()
    {
        $field = $this->instance(['min' => 2]);
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
                'type' => 'block_1',
            ],
            '2' => [
                'uuid' => '2',
                'type' => 'block_1',
            ],
            '3' => [
                'uuid' => '3',
                'type' => 'block_1',
            ],
        ]);

        $this->assertSame(['1', '2', '3'], $field->clean(['1', '2', '3'], $model));
        $this->assertSame(['1', '2'], $field->clean(['1', '2'], $model));

        $this->expectValidationException();
        $field->clean([], $model);
    }

    public function testCleanWithMissingBlock()
    {
        $field = $this->instance();
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
                'type' => 'block_1',
            ],
        ]);

        $this->assertSame(['1'], $field->clean(['1'], $model));

        $this->expectValidationException();
        $field->clean(['1', '2'], $model);
    }

    public function testToPhp()
    {
        $field = $this->instance();
        $model = $this->model($field, [
            '1' => [
                'uuid' => '1',
                'type' => 'block_1',
            ],
            '2' => [
                'uuid' => '2',
                'type' => 'block_2',
            ],
        ]);

        $this->assertInstanceOf(BlocksFieldInstance::class, $field->toPhp([], $model, 'blocks'));
        $this->assertCount(0, $field->toPhp([], $model, 'blocks'));

        $bfi = $field->toPhp(['1', '2'], $model, 'blocks');
        $this->assertCount(2, $bfi);
        $this->assertInstanceOf(Block1::class, $bfi->first());
    }

    private function instance(array $args = []): BlocksField
    {
        $args = $args + [
            'types' => [
                Block1::class,
                Block2::class,
            ],
        ];
        return new BlocksField($args);
    }

    private function model(BlocksField $field, array $blocks = []): WordPressModel
    {
        $em = $this->createMock(EntityManager::class);
        $id = is_multisite() ? 1 : 0;
        $model = new class($em, $id) extends Site {
            public static $blocksField;

            public static function registerFields(): array
            {
                return [
                    'blocks' => self::$blocksField,
                ];
            }
        };
        $model::$blocksField = $field;

        $map = new BlockTypeMap([
            Block1::class => 'block_1',
            Block2::class => 'block_2',
        ]);
        $bs = new BlockSet($map, $model, [
            '0' => (object) [
                'uuid' => '0',
                'type' => $model->rootBlockType(),
            ],
        ] + array_map(function ($block) {
            return (object) $block;
        }, $blocks));
        return $model->cloneWithBlockSet($bs);
    }
}
namespace Awful\Models\Fields\BlocksFieldTest;

use Awful\Models\Block;

class Block1 extends Block
{
}

class Block2 extends Block
{
}
