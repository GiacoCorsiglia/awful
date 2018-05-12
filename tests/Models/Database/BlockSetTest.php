<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\Exceptions\BlockNotFoundException;
use Awful\Models\Database\Exceptions\UuidCollisionException;
use Awful\Models\Database\Query\BlockOwnerIdForSite;
use function Awful\uuid;

class BlockSetTest extends AwfulTestCase
{
    public function testManager()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $set = new BlockSet($manager, $ownerId, []);

        $this->assertSame($manager, $set->manager());
    }

    public function testOwnerId()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $set = new BlockSet($manager, $ownerId, []);

        $this->assertSame($ownerId, $set->ownerId());
    }

    public function testAll()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $block1 = (object) ['uuid' => uuid()];
        $block2 = (object) ['uuid' => uuid()];
        $blocks = [$block1, $block2];
        $set = new BlockSet($manager, $ownerId, $blocks);

        $this->assertSame([
            $block1->uuid => $block1,
            $block2->uuid => $block2,
        ], $set->all());
    }

    public function testGet()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $block1 = (object) ['uuid' => uuid()];
        $block2 = (object) ['uuid' => uuid()];
        $blocks = [$block1, $block2];
        $set = new BlockSet($manager, $ownerId, $blocks);

        $this->assertSame($block1, $set->get($block1->uuid));
        $this->assertSame($block2, $set->get($block2->uuid));
    }

    public function testSet()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $block1 = (object) ['uuid' => uuid()];
        $block2 = (object) ['uuid' => uuid(), 'data' => ['foo' => 'bar']];
        $blocks = [$block1, $block2];
        $set = new BlockSet($manager, $ownerId, $blocks);

        $set->set($block1->uuid, ['fiz', 'buz']);
        $this->assertSame(['fiz', 'buz'], $block1->data, 'Block 1 data referentially updated.');
        $this->assertSame(['foo' => 'bar'], $block2->data, 'Block 2 data preserved');

        $this->expectException(BlockNotFoundException::class);
        $set->set('foobar', []);
    }

    public function testCreate()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $block1 = (object) ['uuid' => uuid()];
        $set = new BlockSet($manager, $ownerId, [$block1]);

        $created1 = $set->create('Type', ['hi' => 'there']);
        $this->assertSame('Type', $created1->type);
        $this->assertSame($ownerId->value(), $created1->{$ownerId->column()});
        $this->assertSame(['hi' => 'there'], $created1->data);
        $this->assertTrue(!empty($created1->uuid));

        $created2 = $set->create('Type', ['hi' => 'there'], 'my-uuid');
        $this->assertSame('my-uuid', $created2->uuid);
        $this->assertSame('Type', $created2->type);
        $this->assertSame($ownerId->value(), $created2->{$ownerId->column()});
        $this->assertSame(['hi' => 'there'], $created2->data);

        $this->assertSame($created1, $set->get($created1->uuid));
        $this->assertSame($created2, $set->get($created2->uuid));

        $this->expectException(UuidCollisionException::class);
        $set->create('', [], $created1->uuid);
    }

    public function testCreateForClass()
    {
        $db = $this->createMock(Database::class);
        $map = new BlockTypeMap([
            'class1' => 'type1',
            'class2' => 'type2',
        ]);
        $manager = new BlockSetManager($db, $map);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);
        $set = new BlockSet($manager, $ownerId, []);

        $uuid1 = uuid();
        $created1 = $set->createForClass('class1', $uuid1);
        $this->assertSame('type1', $created1->type);
        $this->assertSame($uuid1, $created1->uuid);

        $uuid2 = uuid();
        $created1 = $set->createForClass('class2', $uuid2);
        $this->assertSame('type2', $created1->type);
        $this->assertSame($uuid2, $created1->uuid);
    }

    public function testRoot()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $set = new BlockSet($manager, $ownerId, []);

        $root = $set->root();
        $this->assertSame($ownerId->rootBlockType(), $root->type, 'It creates the root if needed');
        $this->assertSame($root, $set->root(), " It doesn't create the root twice.");
    }

    public function testSave()
    {
        $manager = $this->createMock(BlockSetManager::class);
        $ownerId = new BlockOwnerIdForSite(is_multisite() ? 1 : 0);

        $set = new BlockSet($manager, $ownerId, []);

        $manager->expects($this->once())
            ->method('save')
            ->with($this->equalTo($set));

        $set->save();
    }
}
