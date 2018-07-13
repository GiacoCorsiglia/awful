<?php
namespace Awful\Models\Query;

use Awful\AwfulTestCase;
use Awful\Exceptions\ImmutabilityException;
use Awful\Models\Post;

class GenericPostQuerySetTest extends AwfulTestCase
{
    /** @var GenericPostQuerySet */
    private $qs;

    public function setUp()
    {
        parent::setUp();
        $this->qs = new GenericPostQuerySet($this->mockSite());
    }

    public function testAny()
    {
        $this->assertTrue($this->qs->any());
        $this->assertFalse($this->qs->filter(['post__in' => [123456789]])->any());
    }

    public function testFetchAndArrayGetMethods()
    {
        $numPosts = 11;
        // The main Site will already exist.
        $this->factory->post->create_many($numPosts);

        $previousQueryCount = get_num_queries();
        $this->flush_cache();

        $posts = $this->qs->fetch();
        $this->assertCount($numPosts, $posts);
        $this->assertContainsOnlyInstancesOf(Post::class, $posts);

        // Let's make sure each method does not trigger more queries.
        $queryCount = get_num_queries();
        $this->assertSame($previousQueryCount + 2, $queryCount, 'A query was actually run.');

        $this->assertCount($numPosts, $this->qs);

        $this->assertContainsOnlyInstancesOf(Post::class, $this->qs);
        $this->assertArrayHasKey(get_current_blog_id(), $posts);
        $this->assertArrayHasKey(get_current_blog_id(), $this->qs);
        // Test offsetExists.
        $this->assertTrue(isset($this->qs[get_current_blog_id()]));
        foreach ($this->qs as $key => $post) {
            $this->assertSame($post->id(), $key, 'Array should be keyed by Post ID');
        }

        $this->assertSame($posts, $this->qs->fetch());

        $this->assertSame($queryCount, get_num_queries(), 'No additional queries were run');
    }

    public function testFetchById()
    {
        $wpPost = $this->factory->post->create_and_get();
        $post = $this->qs->fetchById($wpPost->ID);
        $this->assertNotNull($post);
        $this->assertSame((int) $wpPost->blog_id, $post->id());

        $this->assertNull($this->qs->fetchById(1234567));
    }

    public function testFilterMethods()
    {
        // $this->assertSame(0, $this->qs->wpPostQuery()->query_vars['number'], 'Defaults to loading all Posts');

        // $this->assertTrue($this->qs->archived(true)->wpPostQuery()->query_vars['archived']);
        // $this->assertTrue($this->qs->deleted(true)->wpPostQuery()->query_vars['deleted']);
        // $this->assertTrue($this->qs->mature(true)->wpPostQuery()->query_vars['mature']);
        // $this->assertTrue($this->qs->public(true)->wpPostQuery()->query_vars['public']);
        // $this->assertTrue($this->qs->spam(true)->wpPostQuery()->query_vars['spam']);

        // $chunkedQueryVars = $this->qs->chunk(2, 5)->wpPostQuery()->query_vars;
        // $this->assertSame(2, $chunkedQueryVars['number']);
        // $this->assertSame(5, $chunkedQueryVars['offset']);
    }

    public function testFirst()
    {
        $this->assertInstanceOf(Post::class, $this->qs->first());
        $this->assertNull($this->qs->chunk(1, 1000000)->first());
    }

    public function testIds()
    {
        // $numPosts = 11;
        // // The main Site will already exist.
        // $this->factory->post->create_many($numPosts);

        // $ids = $this->qs->ids();
        // $this->assertCount($numPosts, $ids);
        // $this->assertContainsOnly('int', $ids, true);
    }

    public function testOffsetSetThrows()
    {
        $this->expectException(ImmutabilityException::class);
        $this->qs[5] = 'foo';
    }

    public function testOffsetUnsetThrows()
    {
        $this->expectException(ImmutabilityException::class);
        unset($this->qs[5]);
    }
}
