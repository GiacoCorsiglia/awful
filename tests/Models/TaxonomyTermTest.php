<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class TaxonomyTermTest extends AwfulTestCase
{
    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::TERM_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        $this->assertSame(3, $this->instance(3)->blockRecordColumnValue());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Term', $this->instance()->rootBlockType());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new TaxonomyTerm($this->mockSite($siteId), 1))->siteId());
    }

    private function instance(int $termId = 1): TaxonomyTerm
    {
        return new TaxonomyTerm($this->mockSite(), $termId);
    }
}
