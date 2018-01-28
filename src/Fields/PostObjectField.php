<?php
namespace Awful\Fields;

use Awful\Models\Model;
use Awful\Models\Posts\Post;

/**
 * Allows saving a reference to another post.
 */
class PostObjectField extends Field
{
    const ACF_TYPE = 'post_object';

    public function forPhp($value, HasFields $owner)
    {
        if ($owner instanceof HasFieldsWithSource) {
            $owner = $owner->getDataSource();
        }
        $site_id = $owner instanceof Model ? $owner->getSiteId() : 0;

        if (empty($this->args['multiple'])) {
            return $this->toPost($value);
        }

        if (!$value) {
            // Have to do this because `(array) false === [ false ]`.
            return [];
        }

        $posts = [];
        foreach ((array) $value as $post_id) {
            if ($post = $this->toPost($post_id)) {
                $posts[$post_id] = $post;
            }
        }
        return $posts;
    }

    /**
     * Tries to convert the value into a post object.
     *
     * @param mixed $value
     * @param int   $site_id
     * @param mixed $value
     *
     * @return Post|null
     */
    private function toPost($value, int $site_id): ?Post
    {
        $value = (int) $value;
        if (!$value) {
            return null;
        }
        $post = Post::id($value, $site_id);
        return $post->isSaved() ? $post : null;
    }
}
