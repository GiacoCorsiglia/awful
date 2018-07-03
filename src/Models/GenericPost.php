<?php
namespace Awful\Models;

use Awful\Exceptions\UnimplementedException;
use Awful\Models\Database\Database;
use Awful\Models\Traits\WordPressModelOwnedBySite;
use Awful\Models\Traits\WordPressModelWithMetaTable;
use WP_Post;

/**
 * Base class for all post types: models that live in the wp_posts table.
 *
 * Implements getters for the default post fields along with the base
 * functionality for loading postmeta.
 */
class GenericPost extends WordPressModel
{
    use WordPressModelOwnedBySite;
    use WordPressModelWithMetaTable;

    /**
     * If `true`, indicates that the post type represented by this class is
     * one of WordPress' defaults, meaning Awful should not try to register it.
     *
     * @var bool
     */
    public const IS_BUILTIN = false;

    /**
     * Slug of the post type represented by this class.
     *
     * @var string
     */
    public const TYPE = '';

    protected const WP_OBJECT_FIELDS = [
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
     * Returns the array of arguments that should be passed to WordPress'
     * `register_post_type()` when registering the `static::TYPE` post type.
     *
     * If a Closure is returned, it will be invoked via the dependency injection
     * container, and is expected to return an array itself.
     *
     * @return array|\Closure Either an array of settings, or a function that
     *                        resolves to an array of settings.
     */
    public static function settings()
    {
        // Don't make this an abstract method since it isn't required for
        // builtin post types.
        throw new UnimplementedException();
    }

    /**
     * Cache of the post author user object.
     *
     * @var User|null
     */
    private $author;

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
     * Cache of the WordPress post object representing this post.
     *
     * @var WP_Post|null
     */
    private $wpPost;

    /**
     * Retrieves the author of this post.
     *
     * @return User|null The post's author, or null if the post doesn't exist or
     *                   does not have an author.
     */
    final public function author(): ?User
    {
        if (!$this->author && ($wpPost = $this->wpPost())) {
            $authorId = $wpPost->post_author;
            $this->author = $authorId ? new User($this->entityManager(), (int) $authorId) : null;
        }
        return $this->author;
    }

    final public function blockRecordColumn(): string
    {
        return Database::POST_COLUMN;
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
    final public function date(string $format = ''): string
    {
        return $this->id ? ($this->callInSiteContext('get_the_date', $format, $this->id) ?: '') : '';
    }

    /**
     * Retrieves the post excerpt.
     *
     * @return string The post excerpt saved on the post, if any.
     */
    final public function excerpt(): string
    {
        return $this->id ? ($this->callInSiteContext('get_the_excerpt', $this->id) ?: '') : '';
    }

    final public function exists(): bool
    {
        return $this->id && $this->wpPost() !== null;
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
    final public function modifiedDate(string $format = ''): string
    {
        return $this->id ? ($this->callInSiteContext('get_the_modified_date', $format, $this->id) ?: '') : '';
    }

    final public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Post';
    }

    /**
     * Retrieves the status of the post using `get_post_status()`.
     *
     * @return string The status of the post, or an empty string if the post
     *                does not exist.
     */
    final public function status(): string
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
    final public function title(): string
    {
        if ($this->title === null) {
            $this->title = $this->id ? ($this->callInSiteContext('get_the_title', $this->id) ?: '') : '';
        }

        return $this->title;
    }

    /**
     * Retrieves the post's type.
     *
     * @return string The post type, or an empty string if no post exists.
     */
    final public function type(): string
    {
        return ($wpPost = $this->wpPost()) ? $wpPost->post_type : '';
    }

    final public function wpObject(): ?object
    {
        return $this->wpPost();
    }

    /**
     * Fetches the WordPress object representing this post, if one exists.
     *
     * @return WP_Post|null The `WP_Post` object corresponding with $this->id,
     *                      or `null` if none exists.
     */
    final public function wpPost(): ?WP_Post
    {
        if ($this->id && !$this->wpPost) {
            $this->wpPost = $this->callInSiteContext('get_post', $this->id) ?: null;
        }
        return $this->wpPost;
    }

    final protected function metaType(): string
    {
        return 'post';
    }
}
