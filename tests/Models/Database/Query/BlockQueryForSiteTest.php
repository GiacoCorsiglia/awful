<?php
namespace Awful\Models\Database\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;
use Awful\Models\Database\Query\Exceptions\EmptyBlockQueryException;

class BlockQueryForSiteTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new BlockQueryForSite($siteId))->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::SITE_COLUMN, $this->instance()->column());
    }

    public function testValues()
    {
        $siteId = is_multisite() ? 3 : 0;
        $this->assertSame([1], $this->instance($siteId)->values());
    }

    public function testIds()
    {
        $siteId = is_multisite() ? 3 : 0;
        $this->assertSame([$siteId], $this->instance($siteId)->ids());
    }

    public function testWithout()
    {
        $siteId = is_multisite() ? 3 : 0;
        $i = $this->instance($siteId);
        $this->assertSame([1], $i->without([2])->values());
        $this->assertSame([], $i->without([$siteId])->values());
    }

    public function testGetOwnerId()
    {
        $siteId = is_multisite() ? 3 : 0;
        $oid = (new BlockQueryForSite($siteId))->getOwnerId($siteId);
        $this->assertTrue($oid instanceof BlockOwnerIdForSite);
        $this->assertSame($siteId, $oid->siteId());
    }

    public function testAny()
    {
        $this->assertTrue($this->instance()->any());
    }

    public function testSql()
    {
        $column = Database::SITE_COLUMN;
        // Should always be `1`
        $this->assertSame("`$column` = 1", $this->instance()->sql());
    }

    public function testSqlWhenEmpty()
    {
        $this->expectException(EmptyBlockQueryException::class);
        $this->instance()->without([is_multisite() ? 1 : 0])->sql();
    }

    private function instance(int $siteId = null): BlockQueryForSite
    {
        if ($siteId === null) {
            $siteId = is_multisite() ? 1 : 0;
        }
        return new BlockQueryForSite($siteId);
    }
}
