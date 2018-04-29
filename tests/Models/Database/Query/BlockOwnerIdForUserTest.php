<?php
namespace Awful\Models\Database\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class BlockOwnerIdForUserTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, $this->instance()->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::USER_COLUMN, $this->instance()->column());
    }

    public function testValue()
    {
        $this->assertSame(3, $this->instance(3)->value());
    }

    public function testSql()
    {
        $column = Database::USER_COLUMN;
        $this->assertSame("`$column` = 3", $this->instance(3)->sql());
    }

    public function testToBlockQuery()
    {
        $i = $this->instance(3);
        $bq = $i->toBlockQuery();
        $this->assertTrue($bq instanceof BlockQueryForUsers);
        $this->assertSame($i->siteId(), $bq->siteId());
        $this->assertSame([$i->value()], $bq->values());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.User', $this->instance()->rootBlockType());
    }

    private function instance(int $userId = 1): BlockOwnerIdForUser
    {
        return new BlockOwnerIdForUser($userId);
    }
}
