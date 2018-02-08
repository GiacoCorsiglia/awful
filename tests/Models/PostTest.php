<?php
namespace Awful\Models;

use Awful\AwfulTestCase;

class PostTest extends AwfulTestCase
{
    public function testGetId()
    {
        $id = $this->factory->post->create();

        $post = Post::id($id);

        $this->assertSame($post->getId(), $id);
    }

    public function testExists()
    {
        $existing_id = $this->factory->post->create();

        $saved_post = Post::id($existing_id);
        $new_post = Post::create();
        $bad_id_post = Post::id(12345678); // No way this exists in the test DB.

        $this->assertSame(true, $saved_post->exists());
        $this->assertSame(false, $new_post->exists());
        $this->assertSame(false, $bad_id_post->exists());
    }

    public function testgetRawFieldValue()
    {
        $id = $this->factory->post->create([
            'post_type' => 'post',
            'meta_input' => [
                'test_meta' => 'test value',
                'test_serialized' => ['foo' => 'bar'],
            ],
        ]);
        // Can't add these in a key => value array
        add_post_meta($id, 'test_multi_meta', 'test value 1');
        add_post_meta($id, 'test_multi_meta', 'test value 2');

        $post = Post::id($id);

        $this->assertSame(null, $post->getRawFieldValue('nonexistent_meta'), 'fetches null meta value');
        $this->assertSame('test value', $post->getRawFieldValue('test_meta'), 'fetches simple meta value');
        $this->assertSame(['foo' => 'bar'], $post->getRawFieldValue('test_serialized'), 'fetches serialized meta value');
        $this->assertSame($post->getRawFieldValue('test_multi_meta'), ['test value 1', 'test value 2'], 'fetches multiple meta values as array');
    }
}
