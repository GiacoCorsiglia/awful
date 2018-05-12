<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;

class BlockOwnerIdForSiteTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, $this->instance($siteId)->siteId());
    }

    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::SITE_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        // Should always be `1` since it's a boolean field.
        $this->assertSame(1, $this->instance()->blockRecordColumnValue());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Site', $this->instance()->rootBlockType());
    }

    private function instance(int $siteId = null): Site
    {
        if ($siteId === null) {
            $siteId = is_multisite() ? 1 : 0;
        } elseif (!is_multisite()) {
            $siteId = 0;
        }
        $em = $this->createMock(EntityManager::class);
        return new Site($em, $siteId);
    }
}
