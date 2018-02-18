<?php
namespace Awful\Models;

use Awful\Exceptions\UnimplementedException;
use Awful\Models\Traits\ModelOwnedBySite;
use Awful\Models\Traits\ModelWithMetaTable;
use WP_Post;

/**
 * Base class for all post types: models that live in the wp_posts table.
 *
 * Implements getters for the default post fields along with the base
 * functionality for loading postmeta.
 */
abstract class GenericPost extends Model
{
    use ModelOwnedBySite;
    use ModelWithMetaTable;

    /**
     * Slug of the post type represented by this class.
     *
     * @var string
     */
    public const TYPE = '';

    /**
     * If `true`, indicates that the post type represented by this class is
     * one of WordPress' defaults, meaning Awful should not try to register it.
     *
     * @var bool
     */
    public const IS_BUILTIN = false;

    private const WORDPRESS_OBJECT_FIELDS = [
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

    /**
     * Returns the array of arguments that should be passed to
     * WordPress' `register_post_type()` when registering the `static::TYPE`
     * post type.
     *
     * If a callable is returned, it will be invoked via the dependency
     * injection container, and is expected to return an array itself.
     *
     * @return array|callable Either an array of settings, or a function that
     *                        resolves to an array of settings.
     */
    public static function getSettings()
    {
        // Don't make this an abstract method since it isn't required for
        // builtin post types.
        throw new UnimplementedException();
    }

    /**
     * Cache of the WordPress post object representing this post.
     *
     * @var \WP_Post|null
     */
    private $wp_post;

    /**
     * Cache of the post status.
     *
     * @var string
     */
    private $status = '';

    /**
     * Cache of the post title.
     *
     * @var string
     */
    private $title = '';

    /**
     * Cache of the post author user object.
     *
     * @var User|null
     */
    private $author;

    final public function exists(): bool
    {
        return (bool) $this->getWpPost();
    }

    /**
     * Fetches the WordPress object representing this post, if one exists.
     *
     * @return WP_Post|null The `WP_Post` object corresponding with $this->id,
     *                      or `null` if none exists.
     */
    final public function getWpPost(): ?WP_Post
    {
        if (!$this->wp_post && $this->id) {
            $this->wp_post = $this->callInSiteContext('get_post', $this->id) ?: null;
        }
        return $this->wp_post;
    }

    /**
     * Retrieves the post's type.
     *
     * @return string The post type, or an empty string if no post exists.
     */
    public function getType(): string
    {
        return ($wp_post = $this->getWpPost()) ? $wp_post->post_type : '';
    }

    /**
     * Retrieves the status of the post using `get_post_status()`.
     *
     * @return string The status of the post, or an empty string if the post
     *                does not exist.
     */
    final public function getStatus(): string
    {
        if ($this->status === null) {
            $this->status = $this->id !== 0
                ? ($this->callInSiteContext('get_post_status', $this->id) ?: '')
                : '';
        }
        return $this->status;
    }

    /**
     * Retrieves the title of the post using `get_the_title()`.
     *
     * @return string The title of this post.
     */
    final public function getTitle(): string
    {
        if ($this->title === null) {
            $this->title = $this->id ? ($this->callInSiteContext('get_the_title', $this->id) ?: '') : '';
        }

        return $this->title;
    }

    /**
     * Retrieves the publish date of this post.
     *
     * @param string $format Optional date format string.
     * @see https://codex.wordpress.org/Formatting_Date_and_Time for the
     *      possible $format strings.
     *
     * @return string Formatted date string
     */
    final public function getDate(string $format = ''): string
    {
        return $this->id ? ($this->callInSiteContext('get_the_date', $format, $this->id) ?: '') : '';
    }

    /**
     * Retrieves the last modified date of this post.
     *
     * @param string $format Optional date format string.
     * @see https://codex.wordpress.org/Formatting_Date_and_Time for the
     *      possible $format strings.
     *
     * @return string Formatted date string
     */
    final public function getModifiedDate(string $format = ''): string
    {
        return $this->id ? ($this->callInSiteContext('get_the_modified_date', $format, $this->id) ?: '') : '';
    }

    /**
     * Retrieves the author of this post.
     *
     * @return User|null The post's author, or null if the post doesn't exist or
     *                   does not have an author.
     */
    final public function getAuthor(): ?User
    {
        if (!$this->author && ($wp_post = $this->getWpPost())) {
            $author_id = $wp_post->post_author;
            $this->author = $author_id ? User::id($author_id) : null;
        }
        return $this->author;
    }

    /**
     * Retrieves the post excerpt.
     *
     * @return string The post excerpt saved on the post, if any.
     */
    final public function getExcerpt(): string
    {
        return $this->id ? ($this->callInSiteContext('get_the_excerpt', $this->id) ?: '') : '';
    }

    final protected function getMetaType(): string
    {
        return 'post';
    }
}
