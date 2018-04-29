<?php
namespace Awful\Models\Database\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class BlockOwnerIdForSiteTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, $this->instance($siteId)->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::SITE_COLUMN, $this->instance()->column());
    }

    public function testValue()
    {
        $this->assertSame(1, $this->instance()->value());
    }

    public function testSql()
    {
        $column = Database::SITE_COLUMN;
        // Should always query for `= 1` since it's a boolean column.
        $this->assertSame("`$column` = 1", $this->instance(3)->sql());
    }

    public function testToBlockQuery()
    {
        $i = $this->instance(1);
        $bq = $i->toBlockQuery();
        $this->assertTrue($bq instanceof BlockQueryForSite);
        $this->assertSame($i->siteId(), $bq->siteId());
        $this->assertSame([$i->value()], $bq->values());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Site', $this->instance()->rootBlockType());
    }

    private function instance(int $siteId = null): BlockOwnerIdForSite
    {
        if ($siteId === null) {
            $siteId = is_multisite() ? 1 : 0;
        } elseif (!is_multisite()) {
            $siteId = 0;
        }
        return new BlockOwnerIdForSite($siteId);
    }
}
