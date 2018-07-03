<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class CommentTest extends AwfulTestCase
{
    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::COMMENT_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        $this->assertSame(3, $this->instance(3)->blockRecordColumnValue());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Comment', $this->instance()->rootBlockType());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new Comment($this->mockSite($siteId), 1))->siteId());
    }

    private function instance(int $commentId = 1): Comment
    {
        return new Comment($this->mockSite(), $commentId);
    }
}
