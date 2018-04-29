<?php
namespace Awful\Models\Database\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;
use Awful\Models\Database\Query\Exceptions\EmptyBlockQueryException;

class BlockQueryForUsersTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $this->assertSame(is_multisite() ? 1 : 0, $this->instance()->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::USER_COLUMN, $this->instance()->column());
    }

    public function testValues()
    {
        $this->assertSame([1, 2, 3], $this->instance(1, 2, 3)->values());
    }

    public function testIds()
    {
        $this->assertSame([1, 2, 3], $this->instance(1, 2, 3)->ids());
    }

    public function testWithout()
    {
        $i = $this->instance(1, 2, 3, 4);
        $this->assertSame([1, 3], $i->without([2, 4])->ids());
    }

    public function testGetOwnerId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $oid = (new BlockQueryForUsers($siteId, 1))->getOwnerId(1);
        $this->assertTrue($oid instanceof BlockOwnerIdForUser);
        $this->assertSame($siteId, $oid->siteId());
        $this->assertSame(1, $oid->value());
    }

    public function testAny()
    {
        $this->assertFalse($this->instance()->any());
        $this->assertTrue($this->instance(1)->any());
    }

    public function testSql()
    {
        $column = Database::USER_COLUMN;
        $this->assertSame("`$column` = 3", $this->instance(3)->sql());
        $this->assertSame("`$column` IN (3,4)", $this->instance(3, 4)->sql());
    }

    public function testSqlWhenEmpty()
    {
        $this->expectException(EmptyBlockQueryException::class);
        $this->instance()->sql();
    }

    private function instance(int ...$userIds): BlockQueryForUsers
    {
        return new BlockQueryForUsers(...$userIds);
    }
}
