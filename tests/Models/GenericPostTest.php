<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;

class GenericPostTest extends AwfulTestCase
{
    public function testAuthor()
    {
        $wpUser = $this->factory->user->create_and_get();
        $post = $this->instanceWith([
            'post_author' => $wpUser->ID,
        ]);
        $this->assertSame($wpUser->ID, $post->author()->id());
        $this->assertNull($this->instanceWith()->author());
    }

    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::POST_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        $this->assertSame(3, $this->instance(3)->blockRecordColumnValue());
    }

    public function testDate()
    {
        $post = $this->instanceWith();
        $wpDate = get_the_date('', $post->id());
        $this->assertSame($wpDate, $post->date());
        $wpDate = get_the_date('D m d', $post->id());
        $this->assertSame($wpDate, $post->date('D m d'));
    }

    public function testEntityManager()
    {
        $site = $this->mockSite();
        $post = new GenericPost($site, 0);
        $this->assertSame($site->entityManager(), $post->entityManager());
    }

    public function testExcerpt()
    {
        $this->assertSame('an excerpt', $this->instanceWith([
            'post_excerpt' => 'an excerpt',
        ])->excerpt());

        $this->assertSame('another excerpt', $this->instanceWith([
            'post_excerpt' => 'another excerpt',
        ])->excerpt());
    }

    public function testExists()
    {
        $wpPost = $this->factory->post->create_and_get();
        $this->assertTrue($this->instance($wpPost->ID)->exists());
        $this->assertFalse($this->instance(1234567)->exists());
        $this->assertFalse($this->instance(0)->exists());
    }

    public function testGetAndUpdateMeta()
    {
        $wpPost1 = $this->factory->post->create_and_get();
        $wpPost2 = $this->factory->post->create_and_get();
        $post1 = $this->instance($wpPost1->ID);
        $post2 = $this->instance($wpPost2->ID);

        update_post_meta($wpPost1->ID, 'test-meta', ['test' => 'value']);
        $this->assertSame(['test' => 'value'], $post1->getMeta('test-meta'));
        $this->assertNull($post2->getMeta('test-meta'));

        $post1->updateMeta('test-meta', ['test' => 'updated']);
        $this->assertSame(['test' => 'updated'], get_post_meta($wpPost1->ID, 'test-meta', true));
        $this->assertSame(['test' => 'updated'], $post1->getMeta('test-meta'));
        $this->assertNull($post2->getMeta('test-meta'));

        $post2->updateMeta('other-meta', ['test' => 'foo']);
        $this->assertSame(['test' => 'foo'], get_post_meta($wpPost2->ID, 'other-meta', true));
        $this->assertSame(['test' => 'foo'], $post2->getMeta('other-meta'));
        $this->assertSame(['test' => 'updated'], $post1->getMeta('test-meta'));

        $post1->updateMeta('test-meta', null);
        $this->assertNull($post1->getMeta('test-meta'));
        $this->assertSame([], get_post_meta($wpPost1->ID, 'test-meta'), 'The meta row was actually deleted when set to `null`');
    }

    public function testId()
    {
        $wpPost = $this->factory->post->create_and_get();
        $this->assertSame($wpPost->ID, $this->instance($wpPost->ID)->id());
        $this->assertSame(1234567, $this->instance(1234567)->id());
        $this->assertSame(0, $this->instance(0)->id());
    }

    public function testModifiedDate()
    {
        $post = $this->instanceWith();
        $wpDate = get_the_modified_date('', $post->id());
        $this->assertSame($wpDate, $post->modifiedDate());
        $wpDate = get_the_modified_date('D m d', $post->id());
        $this->assertSame($wpDate, $post->modifiedDate('D m d'));
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Post', $this->instance()->rootBlockType());
    }

    public function testSite()
    {
        $site = $this->mockSite();
        $this->assertSame($site, (new GenericPost($site, 1))->site());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, (new GenericPost($this->mockSite($siteId), 1))->siteId());
    }

    public function testStatus()
    {
        $this->assertSame('', $this->instance(0)->status());
        // 'publish' is the default new status.
        $this->assertSame('publish', $this->instanceWith()->status());
    }

    public function testTitle()
    {
        $this->assertSame('', $this->instance(0)->title());
        $this->assertSame('a title', $this->instanceWith([
            'post_title' => 'a title',
        ])->title());
    }

    public function testType()
    {
        $this->assertSame('', $this->instance(0)->type());
        // 'post' is the default new type.
        $this->assertSame('post', $this->instanceWith()->type());
    }

    public function testWpPostAndWpObject()
    {
        $wpPost = $this->factory->post->create_and_get();
        $post = $this->instance($wpPost->ID);
        $this->assertSame($wpPost->ID, $post->wpPost()->ID);
        $this->assertSame($post->wpPost(), $post->wpObject());

        $newPost = $this->instance(1234567);
        $this->assertNull($newPost->wpPost());
        $this->assertSame($newPost->wpPost(), $newPost->wpObject());
    }

    private function instance(int $postId = 1): GenericPost
    {
        return new GenericPost($this->mockSite(), $postId);
    }

    private function instanceWith(array $data = []): GenericPost
    {
        $site = $this->mockSite();
        if (is_multisite()) {
            switch_to_blog($site->id());
        }
        $wpPost = $this->factory->post->create_and_get($data);
        if (is_multisite()) {
            restore_current_blog();
        }

        return new GenericPost($site, $wpPost->ID);
    }
}
