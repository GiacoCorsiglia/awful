<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class GenericPostTest extends AwfulTestCase
{
    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::POST_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        $this->assertSame(3, $this->instance(3)->blockRecordColumnValue());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Post', $this->instance()->rootBlockType());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new GenericPost($this->mockSite($siteId), 1))->siteId());
    }

    private function instance(int $postId = 1): GenericPost
    {
        return new GenericPost($this->mockSite(), $postId);
    }
}
