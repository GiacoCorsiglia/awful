<?php
namespace Awful\Models\Database\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class BlockOwnerIdForCommentTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new BlockOwnerIdForComment($siteId, 1))->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::COMMENT_COLUMN, $this->instance()->column());
    }

    public function testValue()
    {
        $this->assertSame(3, $this->instance(3)->value());
    }

    public function testSql()
    {
        $column = Database::COMMENT_COLUMN;
        $this->assertSame("`$column` = 3", $this->instance(3)->sql());
    }

    public function testToBlockQuery()
    {
        $i = $this->instance(3);
        $bq = $i->toBlockQuery();
        $this->assertTrue($bq instanceof BlockQueryForComments);
        $this->assertSame($i->siteId(), $bq->siteId());
        $this->assertSame([$i->value()], $bq->values());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Comment', $this->instance()->rootBlockType());
    }

    private function instance(int $commentId = 1): BlockOwnerIdForComment
    {
        return new BlockOwnerIdForComment(
            is_multisite() ? 1 : 0,
            $commentId
        );
    }
}
