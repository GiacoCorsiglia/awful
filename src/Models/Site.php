<?php
namespace Awful\Models;

use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;
use Awful\Models\Query\GenericPostQuerySet;
use Awful\Models\Traits\WordPressModelWithSiteContext;
use WP_Site;

class Site extends WordPressModel
{
    use WordPressModelWithSiteContext;

    /**
     * Only apply when is_multisite().
     * From the wp_blogs table.
     */
    protected const WP_OBJECT_FIELDS = [
        'blog_id' => 'int',
        'site_id' => 'int',
        'domain' => 'string',
        'path' => 'string',
        'registered' => 'date',
        'last_updated' => 'date',
        'public' => 'bool',
        'archived' => 'bool',
        'mature' => 'bool',
        'spam' => 'bool',
        'deleted' => 'bool',
        'lang_id' => 'int',
    ];

    /**
     * Returns the list of options page classes enabled on this site.
     *
     * @return string[]
     */
    public static function optionsPages(): array
    {
        return [];
    }

    /** @var EntityManager */
    private $entityManager;

    /** @var int */
    private $id;

    /** @var WP_Site|null */
    private $wpSite;

    final public function __construct(
        EntityManager $entityManager,
        int $id
    ) {
        $this->entityManager = $entityManager;
        $this->id = $this->siteId = $id;
    }

    final public function entityManager(): EntityManager
    {
        return $this->entityManager;
    }

    final public function blockRecordColumn(): string
    {
        return Database::SITE_COLUMN;
    }

    final public function blockRecordColumnValue(): int
    {
        return 1; // It's a boolean column.
    }

    final public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Site';
    }

    final public function id(): int
    {
        return $this->id;
    }

    /**
     * Fetches the WordPress object representing this site, if one exists.
     *
     * Will always return `null` if this isn't a multisite install.
     *
     * @return WP_Site|null The `WP_Term` object corresponding with $this->id,
     *                      or `null` if none exists.
     */
    final public function wpSite(): ?WP_Site
    {
        if ($this->id && !$this->wpSite) {
            // `$this->id === 0` always when `!is_multisite()`.
            $this->wpSite = get_site($this->id);
        }
        return $this->wpSite;
    }

    final public function wpObject(): ?object
    {
        return $this->wpSite();
    }

    final public function exists(): bool
    {
        // If not multisite, this always represents the one-and-only global
        // site, which is always "saved".
        return !is_multisite() || ($this->id && $this->wpSite() !== null);
    }

    /**
     * Runs `get_option()` for `$this` site.
     *
     * This is also equivalent to `get_blog_option()`.
     *
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    final public function getOption(string $option, $default = false)
    {
        assert((bool) $option, 'Expected non-empty option');

        return $this->callInSiteContext('get_option', $option, $default);
    }

    /**
     * Runs `update_option()` (or `delete_option()` if `$value` is `null`) for
     * `$this` site.
     *
     * This is also equivalent to `update_blog_option()`.
     *
     * @param string $option
     * @param mixed $value
     * @param bool $autoload
     *
     * @return void
     */
    final public function updateOption(string $option, $value, bool $autoload = true): void
    {
        assert((bool) $option, 'Expected non-empty option');

        if ($value === null) {
            $this->callInSiteContext('delete_option', $option);
        } else {
            $this->callInSiteContext('update_option', $option, $value, $autoload);
        }
    }

    public function allPosts(): GenericPostQuerySet
    {
        return new GenericPostQuerySet($this);
    }

    final protected function clone(): WordPressModel
    {
        return new static($this->entityManager, $this->id);
    }
}
