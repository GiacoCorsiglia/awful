<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\Query\BlockQueryForPosts;
use Awful\Models\Database\Query\BlockQueryForSite;
use stdClass;

class DatabaseMockTest extends AwfulTestCase
{
    public function testDeleteBlocksFor()
    {
        $db = new DatabaseMock();

        $siteId = is_multisite() ? 1 : 0;
        $postId = 5;

        $db->setBlocksForSite($siteId, [
            [
                DatabaseMock::UUID_COLUMN => 'uuid1',
                DatabaseMock::SITE_COLUMN => 1,
                DatabaseMock::TYPE_COLUMN => 'type1',
            ],
            [
                DatabaseMock::UUID_COLUMN => 'uuid2',
                DatabaseMock::SITE_COLUMN => 1,
                DatabaseMock::TYPE_COLUMN => 'type2',
            ],
            [
                DatabaseMock::UUID_COLUMN => 'uuid3',
                DatabaseMock::SITE_COLUMN => 1,
                DatabaseMock::TYPE_COLUMN => 'type3',
            ],
            [
                DatabaseMock::UUID_COLUMN => 'uuid1',
                DatabaseMock::POST_COLUMN => $postId,
                DatabaseMock::TYPE_COLUMN => 'type1',
            ],
            [
                DatabaseMock::UUID_COLUMN => 'uuid2',
                DatabaseMock::POST_COLUMN => $postId,
                DatabaseMock::TYPE_COLUMN => 'type2',
            ],
            [
                DatabaseMock::UUID_COLUMN => 'uuid4',
                DatabaseMock::POST_COLUMN => $postId,
                DatabaseMock::TYPE_COLUMN => 'type3',
            ],
        ]);

        $getCounts = function () use ($db, $siteId, $postId) {
            $forSiteCount = 0;
            $forPostCount = 0;
            foreach ($db->getBlocksForSite($siteId) as $block) {
                if (($block->{DatabaseMock::SITE_COLUMN} ?? null) === 1) {
                    $forSiteCount++;
                }
                if (($block->{DatabaseMock::POST_COLUMN} ?? null) === $postId) {
                    $forPostCount++;
                }
            }
            return [$forSiteCount, $forPostCount];
        };

        // Sanity check.
        [$forSiteCount, $forPostCount] = $getCounts();
        $this->assertSame(3, $forSiteCount);
        $this->assertSame(3, $forPostCount);

        $siteQuery = new BlockQueryForSite($siteId);
        $postQuery = new BlockQueryForPosts($siteId, $postId);

        // Run the deletion.
        $db->deleteBlocksFor($siteQuery, ['uuid1', 'uuid3']);

        // Get new counts.
        [$forSiteCount, $forPostCount] = $getCounts();

        $this->assertSame(1, $forSiteCount, 'Correct number of site blocks remain.');
        $this->assertEmpty(array_filter($db->getBlocksForSite($siteId), function ($block) {
            return $block->{DatabaseMock::SITE_COLUMN} === 1
                && in_array($block->{DatabaseMock::UUID_COLUMN}, ['uuid1', 'uuid3']);
        }), 'Correct site blocks were deleted');

        $this->assertSame(3, $forPostCount, 'It does not delete the post blocks.');
    }

    public function testFetchBlocks()
    {
        $db = new DatabaseMock();

        $siteId = is_multisite() ? 1 : 0;
        $postId1 = 5;
        $postId2 = 10;

        $db->setBlocksForSite($siteId, [
            [
                DatabaseMock::UUID_COLUMN => 'a',
                DatabaseMock::SITE_COLUMN => 1,
                DatabaseMock::TYPE_COLUMN => 'type1',
                DatabaseMock::DATA_COLUMN => ['hello' => 'hi'],
            ],
            [
                DatabaseMock::UUID_COLUMN => 'aa',
                DatabaseMock::SITE_COLUMN => 1,
                DatabaseMock::TYPE_COLUMN => 'type2',
                DatabaseMock::DATA_COLUMN => [],
            ],
            [
                DatabaseMock::UUID_COLUMN => 'b',
                DatabaseMock::POST_COLUMN => $postId1,
                DatabaseMock::TYPE_COLUMN => 'type1',
                DatabaseMock::DATA_COLUMN => ['hello' => 'hi'],
            ],
            [
                DatabaseMock::UUID_COLUMN => 'bb',
                DatabaseMock::POST_COLUMN => $postId1,
                DatabaseMock::TYPE_COLUMN => 'type2',
                DatabaseMock::DATA_COLUMN => [],
            ],
            [
                DatabaseMock::UUID_COLUMN => 'b',
                DatabaseMock::POST_COLUMN => $postId2,
                DatabaseMock::TYPE_COLUMN => 'type1',
                DatabaseMock::DATA_COLUMN => ['hello' => 'hi'],
            ],
            [
                DatabaseMock::UUID_COLUMN => 'bb',
                DatabaseMock::POST_COLUMN => $postId2,
                DatabaseMock::TYPE_COLUMN => 'type2',
                DatabaseMock::DATA_COLUMN => [],
            ],
        ]);

        // NOTE: The below matches the real DatabaseTest exactly.

        $siteQuery = new BlockQueryForSite($siteId);
        $siteBlocks = $db->fetchBlocks($siteQuery);
        $this->assertSame(2, count($siteBlocks), 'Correct number of blocks found');
        // The value is returned as a string.
        $this->assertEquals(1, $siteBlocks[0]->{DatabaseMock::SITE_COLUMN}, 'For site is correct');
        $this->assertEquals(1, $siteBlocks[1]->{DatabaseMock::SITE_COLUMN}, 'For site is correct');

        $this->assertEquals(['hello' => 'hi'], $siteBlocks[0]->{DatabaseMock::DATA_COLUMN}, 'Data is decoded correctly.');

        $allPostQuery = new BlockQueryForPosts($siteId, $postId1, $postId2);
        $allPostBlocks = $db->fetchBlocks($allPostQuery);
        $this->assertSame(4, count($allPostBlocks), 'Correct number of blocks found');

        $onePostQuery = new BlockQueryForPosts($siteId, $postId1);
        $onePostBlocks = $db->fetchBlocks($onePostQuery);
        $this->assertSame(2, count($onePostBlocks), 'Correct number of blocks found');
        // The value is returned as a string.
        $this->assertEquals($postId1, $onePostBlocks[0]->{DatabaseMock::POST_COLUMN}, 'Post ID matches (1)');
        $this->assertEquals($postId1, $onePostBlocks[1]->{DatabaseMock::POST_COLUMN}, 'Post ID matches (2)');
    }

    public function testGetAndSetBlocksForSite()
    {
        $db = new DatabaseMock();

        $siteId = 5;
        $otherSiteId = 10;

        $db->setBlocksForSite($siteId, [
            [
                'uuid' => 'uuid1',
            ],
            (object) [
                'uuid' => 'uuid2',
            ],
        ]);

        $db->setBlocksForSite($otherSiteId, [
            [
                'uuid' => 'uuid3',
            ],
        ]);

        $blocks = $db->getBlocksForSite($siteId);

        $this->assertCount(2, $blocks);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $blocks);
        $this->assertCount(1, array_filter($blocks, function ($block) {
            return $block->uuid === 'uuid1';
        }));
        $this->assertCount(1, array_filter($blocks, function ($block) {
            return $block->uuid === 'uuid2';
        }));

        $this->assertCount(1, $db->getBlocksForSite($otherSiteId));
    }

    public function testSaveBlocks()
    {
        $db = new DatabaseMock();

        $siteId = 5;

        $db->setBlocksForSite($siteId, [
            [
                DatabaseMock::ID_COLUMN => 1,
                DatabaseMock::UUID_COLUMN => 'uuid1',
                DatabaseMock::DATA_COLUMN => ['foo' => 'bar'],
            ],
        ]);

        $db->saveBlocks($siteId, [
            (object) [
                DatabaseMock::ID_COLUMN => 1,
                DatabaseMock::DATA_COLUMN => ['foo2' => 'bar2'],
            ],
            (object) [
                DatabaseMock::UUID_COLUMN => 'uuid2',
                DatabaseMock::TYPE_COLUMN => 'second block type',
            ],
        ]);

        $blocks = $db->getBlocksForSite($siteId);
        $this->assertCount(2, $blocks);

        $updatedBlock = null;
        $newBlock = null;
        foreach ($blocks as $block) {
            if ($block->{DatabaseMock::ID_COLUMN} === 1) {
                $updatedBlock = $block;
            }
            if ($block->{DatabaseMock::UUID_COLUMN} === 'uuid2') {
                $newBlock = $block;
            }
        }

        $this->assertNotNull($updatedBlock);
        $this->assertNotNull($newBlock);
        $this->assertNotSame($updatedBlock, $newBlock);

        $this->assertSame(['foo2' => 'bar2'], $updatedBlock->{DatabaseMock::DATA_COLUMN});

        $newBlockId = $newBlock->{DatabaseMock::ID_COLUMN} ?? null;
        $this->assertTrue(is_int($newBlockId));
        $this->assertTrue($newBlockId > 0);
    }
}
