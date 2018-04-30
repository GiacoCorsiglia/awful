<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
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
        $this->assertTrue(!empty($sets[$siteId]));
        $set = $sets[$siteId];
        $this->assertSame($uuid, $set->get($uuid)->uuid);

        // Now do it all again, but this time should read from cache.  Note that
        // above we `expect()` that the `$db` method will only be called once.
        $sets = $manager->blockSetsForQuery($bq);
        $this->assertSame(1, count($sets));
        $this->assertTrue(!empty($sets[$siteId]));
        $set = $sets[$siteId];
        $this->assertSame($uuid, $set->get($uuid)->uuid);
    }
}
