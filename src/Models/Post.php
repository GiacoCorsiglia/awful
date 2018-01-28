<?php
namespace Awful\Models;

use Awful\Models\Query\PostQuerySet;
use WP_Post;

abstract class Post extends ModelWithMetadata
{
    protected const OBJECT_TYPE = 'post';

    /**
     * Post type slug.
     * @var string
     */
    const TYPE = '';

    const QUERY_SET = PostQuerySet::class;

    const SETTINGS = [];

    const IS_BUILTIN = false;

    private const BUILTIN_FIELDS = [
        'ID' => 'int',
        'post_author' => User::class,
        'post_date' => 'date',
        'post_date_gmt' => 'date',
        'post_content' => 'content',
        'post_title' => 'string',
        'post_excerpt' => 'string',
        'post_status' => 'string',
        'comment status' => 'string',
        'ping_status' => 'string',
        'post_password' => 'string',
        'post_name' => 'string',
        'to_ping' => 'string',
        'post_modified' => 'date',
        'post_modified_gmt' => 'date',
        'post_content_filtered' => 'string',
        'post_parent' => self::class,
        'guid' => 'string',
        'menu_order' => 'int',
        'post_type' => 'string',
        'post_mime_type' => 'string',
        'comment_count' => 'int',
    ];

    /** @var int */
    protected $id;

    /** @var \WP_Post */
    protected $wp_post;

    /** @var string */
    protected $status;

    public static function bootstrap(): ?callable
    {
        if (!static::IS_BUILTIN) {
            register_post_type(static::TYPE, static::getSettings());
        }
    }

    protected static function getSettings(): array
    {
        return static::SETTINGS;
    }

    public static function query(): PostQuerySet
    {
        return new PostQuerySet();
    }

    final public function isSaved(): bool
    {
        return (bool) $this->getStatus();
    }

    final public function getStatus(): string
    {
        if ($this->status === null) {
            $this->status = $this->id === 0 ? '' : (get_post_status($this->id) ?: '');
        }
        return $this->status;
    }

    /**
     * @return \WP_Post The WP post object corresponding with $this->id
     */
    final protected function getWpPost(): ?WP_Post
    {
        if (!$this->getStatus()) {
            return null;
        }

        if (!$this->wp_post) {
            $this->wp_post = get_post($this->id);
        }
        return $this->wp_post;
    }
}
