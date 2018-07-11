<?php
namespace Awful\Models\Block;

use Awful\AwfulTestCase;
use Awful\Models\Block\BlockTest\TestBlock;
use Awful\Models\Database\BlockSet;
use Awful\Models\Database\BlockSetManager;
use Awful\Models\Database\EntityManager;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Site;

class BlockTest extends AwfulTestCase
{
    public function testBlockSet()
    {
        [$block, , $bs] = $this->instance();
        $this->assertSame($bs, $block->blockSet());
    }

    public function testExists()
    {
        [$block] = $this->instance();
        $this->assertFalse($block->exists());
        [$block] = $this->instance([], 5);
        $this->assertTrue($block->exists());
    }

    public function testId()
    {
        [$block] = $this->instance();
        $this->assertSame(0, $block->id());
        [$block] = $this->instance([], 5);
        $this->assertSame(5, $block->id());
    }

    public function testLabelMethodReadsConstant()
    {
        $this->assertSame('Test', TestBlock::label());
    }

    public function testOwner()
    {
        [$block, $site] = $this->instance();
        $this->assertSame($site, $block->owner());
    }

    public function testReloadBlocks()
    {
        $bsm = $this->createMock(BlockSetManager::class);
        $em = new EntityManager($bsm);
        $site = new Site($em, is_multisite() ? 1 : 0);
        $bsm->expects($this->exactly(2))
            ->method('fetchBlockSet')
            ->willReturnCallback(function () {
                return $this->createMock(BlockSet::class);
            });

        $block = new TestBlock($site, 'uuid');

        $bs1 = $block->blockSet();
        $this->assertSame($bs1, $block->blockSet());
        $site->reloadBlocks();
        $this->assertSame($bs1, $block->blockSet());
        $block->reloadBlocks();
        $this->assertNotSame($bs1, $block->blockSet());
    }

    public function testUuid()
    {
        [$block] = $this->instance();
        $this->assertSame('uuid1', $block->uuid());
    }

    private function instance(array $data = [], int $id = null): array
    {
        $bsm = $this->createMock(BlockSetManager::class);

        $em = new EntityManager($bsm);
        $site = new Site($em, is_multisite() ? 1 : 0);

        $map = new BlockTypeMap([
            TestBlock::class => 'TestBlock',
        ]);

        $bsm->expects($this->once())
            ->method('fetchBlockSet')
            ->willReturn($bs = new BlockSet($map, $site, [
                'uuid1' => (object) [
                    'uuid' => 'uuid1',
                    'id' => $id,
                    'type' => 'TestBlock',
                    'data' => $data,
                ],
            ]));

        return [new TestBlock($site, 'uuid1'), $site, $bs];
    }
}
namespace Awful\Models\Block\BlockTest;

use Awful\models\Block;
use Awful\Models\Fields\TextField;

class TestBlock extends Block
{
    protected const LABEL = 'Test';

    protected static function registerFields(): array
    {
        return [
            'field' => new TextField(),
        ];
    }
}
