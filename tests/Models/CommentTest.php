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

    public function testEntityManager()
    {
        $site = $this->mockSite();
        $comment = new Comment($site, 0);
        $this->assertSame($site->entityManager(), $comment->entityManager());
    }

    public function testExists()
    {
        $wpComment = $this->factory->comment->create_and_get();
        $this->assertTrue($this->instance($wpComment->comment_ID)->exists());
        $this->assertFalse($this->instance(1234567)->exists());
        $this->assertFalse($this->instance(0)->exists());
    }

    public function testGetAndUpdateMeta()
    {
        $wpComment1 = $this->factory->comment->create_and_get();
        $wpComment2 = $this->factory->comment->create_and_get();
        $comment1 = $this->instance($wpComment1->comment_ID);
        $comment2 = $this->instance($wpComment2->comment_ID);

        update_comment_meta($wpComment1->comment_ID, 'test-meta', ['test' => 'value']);
        $this->assertSame(['test' => 'value'], $comment1->getMeta('test-meta'));
        $this->assertNull($comment2->getMeta('test-meta'));

        $comment1->updateMeta('test-meta', ['test' => 'updated']);
        $this->assertSame(['test' => 'updated'], get_comment_meta($wpComment1->comment_ID, 'test-meta', true));
        $this->assertSame(['test' => 'updated'], $comment1->getMeta('test-meta'));
        $this->assertNull($comment2->getMeta('test-meta'));

        $comment2->updateMeta('other-meta', ['test' => 'foo']);
        $this->assertSame(['test' => 'foo'], get_comment_meta($wpComment2->comment_ID, 'other-meta', true));
        $this->assertSame(['test' => 'foo'], $comment2->getMeta('other-meta'));
        $this->assertSame(['test' => 'updated'], $comment1->getMeta('test-meta'));

        $comment1->updateMeta('test-meta', null);
        $this->assertNull($comment1->getMeta('test-meta'));
        $this->assertSame([], get_comment_meta($wpComment1->comment_ID, 'test-meta'), 'The meta row was actually deleted when set to `null`');
    }

    public function testId()
    {
        $wpComment = $this->factory->comment->create_and_get();
        $this->assertSame((int) $wpComment->comment_ID, $this->instance($wpComment->comment_ID)->id());
        $this->assertSame(1234567, $this->instance(1234567)->id());
        $this->assertSame(0, $this->instance(0)->id());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Comment', $this->instance()->rootBlockType());
    }

    public function testSite()
    {
        $site = $this->mockSite();
        $this->assertSame($site, (new Comment($site, 1))->site());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new Comment($this->mockSite($siteId), 1))->siteId());
    }

    public function testWpCommentAndWpObject()
    {
        $wpComment = $this->factory->comment->create_and_get();
        $comment = $this->instance($wpComment->comment_ID);
        $this->assertSame($wpComment->comment_ID, $comment->wpComment()->comment_ID);
        $this->assertSame($comment->wpComment(), $comment->wpObject());

        $newComment = $this->instance(1234567);
        $this->assertNull($newComment->wpComment());
        $this->assertSame($newComment->wpComment(), $newComment->wpObject());
    }

    private function instance(int $commentId = 1): Comment
    {
        return new Comment($this->mockSite(), $commentId);
    }

    private function instanceWith(array $data = []): TaxonomyTerm
    {
        $site = $this->mockSite();
        if (is_multisite()) {
            switch_to_blog($site->id());
        }
        $wpComment = $this->factory->comment->create_and_get($data);
        if (is_multisite()) {
            restore_current_blog();
        }
        return new TaxonomyTerm($site, $wpComment->comment_ID);
    }
}
