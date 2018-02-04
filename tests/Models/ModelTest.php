<?php
namespace Awful\Models;

use Awful\AwfulTestCase;

class ModelTest extends AwfulTestCase
{
    public function testBlogPost()
    {
        $id = wp_insert_post([
            'post_type' => 'post',
            'post_title' => 'My test post',
        ]);
        add_post_meta($id, 'test_meta', 'test value');

        $post = Post::id($id);

        $this->assertSame($post->getId(), $id);

        $this->assertSame(Post::id($id), $post);

        $this->assertSame($post->getRaw('test_meta'), 'test value');
    }

    public function testMultipleMeta()
    {
        $id = wp_insert_post([
            'post_type' => 'post',
            'post_title' => 'My test post',
        ]);
        add_post_meta($id, 'test_meta', 'test value 1');
        add_post_meta($id, 'test_meta', 'test value 2');

        $post = Post::id($id);

        $this->assertSame($post->getRaw('test_meta'), ['test value 1', 'test value 2']);
    }
}
