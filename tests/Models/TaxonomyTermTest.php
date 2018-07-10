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

    public function testEntityManager()
    {
        $site = $this->mockSite();
        $term = new TaxonomyTerm($site, 0);
        $this->assertSame($site->entityManager(), $term->entityManager());
    }

    public function testExists()
    {
        $wpTerm = $this->factory->term->create_and_get();
        $this->assertTrue($this->instance($wpTerm->term_id)->exists());
        $this->assertFalse($this->instance(1234567)->exists());
        $this->assertFalse($this->instance(0)->exists());
    }

    public function testGetAndUpdateMeta()
    {
        $wpTerm1 = $this->factory->term->create_and_get();
        $wpTerm2 = $this->factory->term->create_and_get();
        $term1 = $this->instance($wpTerm1->term_id);
        $term2 = $this->instance($wpTerm2->term_id);

        update_term_meta($wpTerm1->term_id, 'test-meta', ['test' => 'value']);
        $this->assertSame(['test' => 'value'], $term1->getMeta('test-meta'));
        $this->assertNull($term2->getMeta('test-meta'));

        $term1->updateMeta('test-meta', ['test' => 'updated']);
        $this->assertSame(['test' => 'updated'], get_term_meta($wpTerm1->term_id, 'test-meta', true));
        $this->assertSame(['test' => 'updated'], $term1->getMeta('test-meta'));
        $this->assertNull($term2->getMeta('test-meta'));

        $term2->updateMeta('other-meta', ['test' => 'foo']);
        $this->assertSame(['test' => 'foo'], get_term_meta($wpTerm2->term_id, 'other-meta', true));
        $this->assertSame(['test' => 'foo'], $term2->getMeta('other-meta'));
        $this->assertSame(['test' => 'updated'], $term1->getMeta('test-meta'));

        $term1->updateMeta('test-meta', null);
        $this->assertNull($term1->getMeta('test-meta'));
        $this->assertSame([], get_term_meta($wpTerm1->term_id, 'test-meta'), 'The meta row was actually deleted when set to `null`');
    }

    public function testId()
    {
        $wpTerm = $this->factory->term->create_and_get();
        $this->assertSame($wpTerm->term_id, $this->instance($wpTerm->term_id)->id());
        $this->assertSame(1234567, $this->instance(1234567)->id());
        $this->assertSame(0, $this->instance(0)->id());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Term', $this->instance()->rootBlockType());
    }

    public function testSite()
    {
        $site = $this->mockSite();
        $this->assertSame($site, (new TaxonomyTerm($site, 1))->site());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new TaxonomyTerm($this->mockSite($siteId), 1))->siteId());
    }

    public function testWpTermAndWpObject()
    {
        $wpTerm = $this->factory->term->create_and_get();
        $term = $this->instance($wpTerm->term_id);
        $this->assertSame($wpTerm->term_id, $term->wpTerm()->term_id);
        $this->assertSame($term->wpTerm(), $term->wpObject());

        $newTerm = $this->instance(1234567);
        $this->assertNull($newTerm->wpTerm());
        $this->assertSame($newTerm->wpTerm(), $newTerm->wpObject());
    }

    private function instance(int $termId = 1): TaxonomyTerm
    {
        return new TaxonomyTerm($this->mockSite(), $termId);
    }

    private function instanceWith(array $data = []): TaxonomyTerm
    {
        $site = $this->mockSite();
        if (is_multisite()) {
            switch_to_blog($site->id());
        }
        $wpTerm = $this->factory->term->create_and_get($data);
        if (is_multisite()) {
            restore_current_blog();
        }
        return new TaxonomyTerm($site, $wpTerm->term_id);
    }
}
