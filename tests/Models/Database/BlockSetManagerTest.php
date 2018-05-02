<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\Query\BlockQueryForPosts;
use Awful\Models\Database\Query\BlockQueryForSite;
use function Awful\uuid;

class BlockSetManagerTest extends AwfulTestCase
{
    public function testBlockTypeMap()
    {
        $db = $this->createMock(Database::class);
        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);

        $this->assertSame($map, $manager->blockTypeMap());
    }

    public function testBlockSetsForQueryWithSiteQuery()
    {
        if (is_multisite()) {
            $siteId = (int) $this->factory->blog->create_and_get()->blog_id;
        } else {
            $siteId = 0;
        }

        $db = $this->createMock(Database::class);
        $uuid = uuid();
        $db->expects($this->once()) // The second call should be cached.
            ->method('fetchBlocks')
            ->willReturn([
                (object) [
                    'id' => 1,
                    'uuid' => $uuid,
                    'type' => 'hi',
                    'data' => [],
                ],
                (object) [
                    'id' => 2,
                    'uuid' => uuid(),
                    'type' => 'yo',
                    'data' => [],
                ],
            ]);
        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);

        $bq = new BlockQueryForSite($siteId);

        $sets = $manager->blockSetsForQuery($bq);
        $this->assertSame(1, count($sets));
        $this->assertTrue(!empty($sets[$siteId]) && $sets[$siteId] instanceof BlockSet);
        $set = $sets[$siteId];
        $this->assertSame($uuid, $set->get($uuid)->uuid);

        // Now do it all again, but this time should read from cache.  Note that
        // above we `expect()` that the `$db` method will only be called once.
        $sets = $manager->blockSetsForQuery($bq);
        $this->assertSame(1, count($sets));
        $this->assertTrue(!empty($sets[$siteId]) && $sets[$siteId] instanceof BlockSet);
        $set = $sets[$siteId];
        $this->assertSame($uuid, $set->get($uuid)->uuid);
    }

    public function testBlockSetsForQueryWithPostsQuery()
    {
        if (is_multisite()) {
            $siteId = (int) $this->factory->blog->create_and_get()->blog_id;
        } else {
            $siteId = 0;
        }

        if ($siteId) {
            switch_to_blog($siteId);
        }
        $postId1 = $this->factory->post->create_and_get()->ID;
        $postId2 = $this->factory->post->create_and_get()->ID;
        $postId3 = $this->factory->post->create_and_get()->ID;
        if ($siteId) {
            restore_current_blog();
        }

        $db = $this->createMock(Database::class);
        $uuid1 = uuid();
        $uuid2 = uuid();
        $db->expects($this->once()) // The second call should be cached.
            ->method('fetchBlocks')
            ->willReturn([
                (object) [
                    'id' => 1,
                    Database::POST_COLUMN => $postId1,
                    'uuid' => $uuid1,
                    'type' => 'hi',
                    'data' => [],
                ],
                (object) [
                    'id' => 2,
                    Database::POST_COLUMN => $postId1,
                    'uuid' => uuid(),
                    'type' => 'yo',
                    'data' => [],
                ],
                (object) [
                    'id' => 1,
                    Database::POST_COLUMN => $postId2,
                    'uuid' => $uuid2,
                    'type' => 'hi',
                    'data' => [],
                ],
                (object) [
                    'id' => 2,
                    Database::POST_COLUMN => $postId2,
                    'uuid' => uuid(),
                    'type' => 'yo',
                    'data' => [],
                ],
                // No blocks for $postId3
            ]);
        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);

        $bq = new BlockQueryForPosts($siteId, $postId1, $postId2, $postId3);

        $sets = $manager->blockSetsForQuery($bq);
        $this->assertSame(3, count($sets), '3 BlockSets returned.');
        $this->assertTrue(!empty($sets[$postId1]) && $sets[$postId1] instanceof BlockSet);
        $this->assertTrue(!empty($sets[$postId2]) && $sets[$postId2] instanceof BlockSet);
        $this->assertTrue(!empty($sets[$postId3]) && $sets[$postId3] instanceof BlockSet);
        $set1 = $sets[$postId1];
        $set2 = $sets[$postId2];
        $this->assertSame($uuid1, $set1->get($uuid1)->uuid);
        $this->assertSame($uuid2, $set2->get($uuid2)->uuid);

        // Now do it all again, but this time should read from cache.  Note that
        // above we `expect()` that the `$db` method will only be called once.
        $sets = $manager->blockSetsForQuery($bq);
        $this->assertSame(3, count($sets), '3 BlockSets returned from cache.');
        $this->assertTrue(!empty($sets[$postId1]) && $sets[$postId1] instanceof BlockSet);
        $this->assertTrue(!empty($sets[$postId2]) && $sets[$postId2] instanceof BlockSet);
        $this->assertTrue(!empty($sets[$postId3]) && $sets[$postId3] instanceof BlockSet);
        $set1 = $sets[$postId1];
        $set2 = $sets[$postId2];
        $this->assertSame($uuid1, $set1->get($uuid1)->uuid);
        $this->assertSame($uuid2, $set2->get($uuid2)->uuid);
    }
}
