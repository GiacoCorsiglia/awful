<?php
namespace Awful\Models\Fields;

use Awful\Models\GenericPost;
use Awful\Models\HasFields;
use Awful\Models\SubModel;

/**
 * Allows saving a reference to another post.
 */
class PostObjectField extends Field
{
    const ACF_TYPE = 'post_object';

    public function forPhp($value, HasFields $owner, string $field_name)
    {
        if ($owner instanceof SubModel) {
            $owner = $owner->getDataSource();
        }
        $site_id = $owner && method_exists($owner, 'getSiteId') ? $owner->getSiteId() : 0;

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
     * @return GenericPost|null
     */
    private function toPost($value, int $site_id): ?GenericPost
    {
        $value = (int) $value;
        if (!$value) {
            return null;
        }
        $post = GenericPost::id($value, $site_id);
        return $post->exists() ? $post : null;
    }
}
