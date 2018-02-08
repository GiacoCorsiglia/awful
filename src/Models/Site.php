<?php
namespace Awful\Models;

use Awful\Models\Fields\FieldsResolver;
use Awful\Models\Traits\ModelWithSiteContext;

class Site extends Model
{
    use ModelWithSiteContext;

    protected const FIELD_NAME_PREFIX = 'options_';

    /**
     * Only apply when is_multisite().
     * From the wp_blogs table.
     */
    protected const WORDPRESS_OBJECT_FIELDS = [
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
    public static function getOptionsPages(): array
    {
        return [];
    }

    final public static function getFields()
    {
        return function (FieldsResolver $resolver) {
            $fields = [];
            foreach (static::getOptionsPages() as $options_page) {
                $fields += $resolver->resolve($options_page);
            }
            return $fields;
        };
    }

    protected function __construct(
        int $id = 0,
        FieldsResolver $resolver = null
    ) {
        assert(is_multisite() || $id === 0, 'Expected `$id` of 0 when non-multisite');

        $this->id = $this->site_id = $id;

        $this->initializeFieldsResolver($resolver);
    }

    final public function exists(): bool
    {
        // If not multisite, this always represents the one-and-only global
        // site, which is always "saved".
        return !is_multisite() || ($this->id && (bool) get_site($this->id));
    }

    final protected function fetchData(): void
    {
        global $wpdb;

        // if ($cached = wp_cache_get(self::cacheKey($this->id))) {
        //     $this->data = $cached;
        // }

        $result = $wpdb->get_results(
            "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '"
            . static::FIELD_NAME_PREFIX
            . "%'"
        );

        $l = strlen(self::FIELD_NAME_PREFIX);
        $this->data = [];
        foreach ($result as $row) {
            // Remove the 'options_' prefix.  Ideally would do this with SUBSTR() in SQL
            // but the object WordPress returns is kinda funny and it probably doesn't matter.
            $this->data[substr($row->option_name, $l)] = $row->option_value;
        }

        // wp_cache_set(self::cacheKey($this->id), $this->data);
    }
}
