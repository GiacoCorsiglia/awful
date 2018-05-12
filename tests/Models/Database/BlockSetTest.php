<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\Exceptions\BlockNotFoundException;
use Awful\Models\Database\Exceptions\UuidCollisionException;
use function Awful\uuid;

class BlockSetTest extends AwfulTestCase
{
    public function blockTypeMap()
    {
        $map = new BlockTypeMap([]);
        $owner = $this->mockSite();

        $set = new BlockSet($map, $owner, []);

        $this->assertSame($map, $set->blockTypeMap());
    }

    public function testOwner()
    {
        $map = new BlockTypeMap([]);
        $owner = $this->mockSite();

        $set = new BlockSet($map, $owner, []);

        $this->assertSame($owner, $set->owner());
    }

    public function testAll()
    {
        $block1 = (object) ['uuid' => uuid()];
        $block2 = (object) ['uuid' => uuid()];
        $blocks = [$block1, $block2];
        $set = new BlockSet(new BlockTypeMap([]), $this->mockSite(), $blocks);

        $this->assertSame([
            $block1->uuid => $block1,
            $block2->uuid => $block2,
        ], $set->all());
    }

    public function testGet()
    {
        $block1 = (object) ['uuid' => uuid()];
        $block2 = (object) ['uuid' => uuid()];
        $blocks = [$block1, $block2];
        $set = new BlockSet(new BlockTypeMap([]), $this->mockSite(), $blocks);

        $this->assertSame($block1, $set->get($block1->uuid));
        $this->assertSame($block2, $set->get($block2->uuid));
    }

    public function testSet()
    {
        $block1 = (object) ['uuid' => uuid()];
        $block2 = (object) ['uuid' => uuid(), 'data' => ['foo' => 'bar']];
        $blocks = [$block1, $block2];
        $set = new BlockSet(new BlockTypeMap([]), $this->mockSite(), $blocks);

        $set->set($block1->uuid, ['fiz', 'buz']);
        $this->assertSame(['fiz', 'buz'], $block1->data, 'Block 1 data referentially updated.');
        $this->assertSame(['foo' => 'bar'], $block2->data, 'Block 2 data preserved');

        $this->expectException(BlockNotFoundException::class);
        $set->set('foobar', []);
    }

    public function testCreate()
    {
        $owner = $this->mockSite();
        $block1 = (object) ['uuid' => uuid()];
        $set = new BlockSet(new BlockTypeMap([]), $owner, [$block1]);

        $created1 = $set->create('Type', ['hi' => 'there']);
        $this->assertSame('Type', $created1->type);
        $this->assertSame($owner->blockRecordColumnValue(), $created1->{$owner->blockRecordColumn()});
        $this->assertSame(['hi' => 'there'], $created1->data);
        $this->assertTrue(!empty($created1->uuid));

        $created2 = $set->create('Type', ['hi' => 'there'], 'my-uuid');
        $this->assertSame('my-uuid', $created2->uuid);
        $this->assertSame('Type', $created2->type);
        $this->assertSame($owner->blockRecordColumnValue(), $created2->{$owner->blockRecordColumn()});
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
        $set = new BlockSet($map, $this->mockSite(), []);

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
        $owner = $this->mockSite();
        $set = new BlockSet(new BlockTypeMap([]), $owner, []);

        $root = $set->root();
        $this->assertSame($owner->rootBlockType(), $root->type, 'It creates the root if needed');
        $this->assertSame($root, $set->root(), " It doesn't create the root twice.");
    }
}
