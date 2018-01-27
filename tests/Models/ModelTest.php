<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Posts\BlogPost;

class ModelTest extends AwfulTestCase
{
    public function testBlogPost()
    {
        $id = wp_insert_post([
            'post_type' => 'post',
            'post_title' => 'My test post',
        ]);
        add_post_meta($id, 'test_meta', 'test value');

        $post = BlogPost::id($id);

        $this->assertSame($post->getId(), $id);

        $this->assertSame(BlogPost::id($id), $post);

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

        $post = BlogPost::id($id);

        $this->assertSame($post->getRaw('test_meta'), ['test value 1', 'test value 2']);
    }
}
