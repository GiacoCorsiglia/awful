<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\Exceptions\SiteMismatchException;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Database\Query\BlockQueryForPosts;
use Awful\Models\Database\Query\BlockQueryForSite;
use Awful\Models\GenericPost;
use function Awful\uuid;

class BlockSetManagerTest extends AwfulTestCase
{
    public function testDeleteBlocksFor()
    {
        $siteId = is_multisite() ? 1 : 0;
        $site = $this->mockSite($siteId);

        $uuids = ['uuid1', 'uuid2'];

        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('deleteBlocksFor')
            ->with($this->callback(function (BlockQueryForSite $bq) use ($siteId) {
                return $bq->siteId() === $siteId;
            }), $uuids);

        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);

        $manager->deleteBlocksFor($site, $uuids);
    }

    public function testFetchAndPrefetchBlockSetsForPosts()
    {
        if (is_multisite()) {
            $siteId = (int) $this->factory->blog->create_and_get()->blog_id;
        } else {
            $siteId = 0;
        }

        $site = $this->mockSite($siteId);

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
        // 4 total uncached calls.
        $db->expects($this->exactly(4))
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

        $post1 = new GenericPost($site, $postId1);
        $post2 = new GenericPost($site, $postId2);
        $post3 = new GenericPost($site, $postId3);

        $set1 = $manager->fetchBlockSet($post1); // Uncached call 1.
        $set2 = $manager->fetchBlockSet($post2); // Uncached call 2.
        $set3 = $manager->fetchBlockSet($post3); // Uncached call 3.
        $this->assertSame(2, count($set1->all()));
        $this->assertSame($uuid1, $set1->get($uuid1)->uuid);
        $this->assertSame(2, count($set2->all()));
        $this->assertSame($uuid2, $set2->get($uuid2)->uuid);
        $this->assertSame(0, count($set3->all()));

        // Now do it all again, but this time should read from cache.  Note that
        // above we `expect()` that the `$db` method will only be called once.
        $set1 = $manager->fetchBlockSet($post1);
        $set2 = $manager->fetchBlockSet($post2);
        $set3 = $manager->fetchBlockSet($post3);
        $this->assertSame(2, count($set1->all()));
        $this->assertSame($uuid1, $set1->get($uuid1)->uuid);
        $this->assertSame(2, count($set2->all()));
        $this->assertSame($uuid2, $set2->get($uuid2)->uuid);
        $this->assertSame(0, count($set3->all()));

        // Now flush the cache and do it all again, but with prefetching.
        wp_cache_flush();

        $manager->prefetchBlockRecords(new BlockQueryForPosts(
            $siteId,
            $postId1,
            $postId2,
            $postId3
        )); // Uncached call 4.

        $set1 = $manager->fetchBlockSet($post1);
        $set2 = $manager->fetchBlockSet($post2);
        $set3 = $manager->fetchBlockSet($post3);
        $this->assertSame(2, count($set1->all()));
        $this->assertSame($uuid1, $set1->get($uuid1)->uuid);
        $this->assertSame(2, count($set2->all()));
        $this->assertSame($uuid2, $set2->get($uuid2)->uuid);
        $this->assertSame(0, count($set3->all()));
    }

    public function testFetchBlockSetForSite()
    {
        if (is_multisite()) {
            $siteId = (int) $this->factory->blog->create_and_get()->blog_id;
        } else {
            $siteId = 0;
        }

        $site = $this->mockSite($siteId);

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

        $set = $manager->fetchBlockSet($site);
        $this->assertSame($uuid, $set->get($uuid)->uuid);

        // Now do it all again, but this time should read from cache.  Note that
        // above we `expect()` that the `$db` method will only be called once.
        $set = $manager->fetchBlockSet($site);
        $this->assertSame($uuid, $set->get($uuid)->uuid);
    }

    public function testFetchBlockSetThreadsBlockTypeMap()
    {
        $site = $this->mockSite();
        $db = $this->createMock(Database::class);
        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);
        $set = $manager->fetchBlockSet($site);
        $this->assertSame($map, $set->blockTypeMap());
    }

    public function testSave()
    {
        $siteId = is_multisite() ? 1 : 0;

        $uuid = uuid();
        $block1 = (object) [
            'uuid' => $uuid,
        ];
        $block2 = (object) [
            'uuid' => $uuid,
        ];

        $db = $this->createMock(Database::class);

        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);

        $site = $this->mockSite();
        $post = new GenericPost($site, 5);
        $bs1 = new BlockSet($map, $site, [
            $block1,
        ]);
        $bs2 = new BlockSet($map, $post, [
            $block2,
        ]);

        $db->expects($this->once())
            ->method('saveBlocks')
            ->with($siteId, $this->callback(function (array $blocks) use ($block1, $block2): bool {
                // We don't care about the order.
                return $blocks === [$block1, $block2] || $blocks === [$block2, $block1];
            }));

        // Run the save.
        $manager->save($bs1, $bs2);

        // Ensure it referentially updates the blocks with the correct owner
        // column values.
        $this->assertSame($site->blockRecordColumnValue(), $block1->{$site->blockRecordColumn()});
        $this->assertSame($post->blockRecordColumnValue(), $block2->{$post->blockRecordColumn()});
    }

    public function testSaveRejectsBlockSetsForMultipleSites()
    {
        $this->skipWithoutMultisite();

        $db = $this->createMock(Database::class);
        $map = new BlockTypeMap([]);
        $manager = new BlockSetManager($db, $map);

        $bs1 = new BlockSet($map, $this->mockSite(1), []);
        $bs2 = new BlockSet($map, $this->mockSite(2), []);

        $this->expectException(SiteMismatchException::class);
        $manager->save($bs1, $bs2);
    }
}
