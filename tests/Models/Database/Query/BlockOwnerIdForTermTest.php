<?php
namespace Awful\Models\Database\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class BlockOwnerIdForTermTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new BlockOwnerIdForTerm($siteId, 1))->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::TERM_COLUMN, $this->instance()->column());
    }

    public function testValue()
    {
        $this->assertSame(3, $this->instance(3)->value());
    }

    public function testSql()
    {
        $column = Database::TERM_COLUMN;
        $this->assertSame("`$column` = 3", $this->instance(3)->sql());
    }

    public function testToBlockQuery()
    {
        $i = $this->instance(3);
        $bq = $i->toBlockQuery();
        $this->assertTrue($bq instanceof BlockQueryForTerms);
        $this->assertSame($i->siteId(), $bq->siteId());
        $this->assertSame([$i->value()], $bq->values());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Term', $this->instance()->rootBlockType());
    }

    private function instance(int $termId = 1): BlockOwnerIdForTerm
    {
        return new BlockOwnerIdForTerm(
            is_multisite() ? 1 : 0,
            $termId
        );
    }
}
