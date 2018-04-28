<?php
namespace Awful\Models;

use Awful\Models\Traits\WordPressModelOwnedBySite;
use Awful\Models\Traits\WordPressModelWithMetaTable;
use WP_Term;

class TaxonomyTerm extends WordPressModel
{
    use WordPressModelOwnedBySite;
    use WordPressModelWithMetaTable;

    protected const WP_OBJECT_FIELDS = [
        'term_id' => 'int',
        'name' => 'string',
        'slug' => 'string',
        'term_group' => 'int',
    ];

    /** @var WP_Term|null */
    private $wpTerm;

    /**
     * Fetches the WordPress object representing this term, if one exists.
     *
     * @return WP_Term|null The `WP_Term` object corresponding with $this->id,
     *                      or `null` if none exists.
     */
    final public function wpTerm(): ?WP_Term
    {
        if ($this->id && !$this->wpTerm) {
            $this->wpTerm = $this->callInSiteContext('get_term', $this->id);
        }
        return $this->wpTerm;
    }

    final public function wpObject(): ?object
    {
        return $this->wpTerm();
    }

    final public function exists(): bool
    {
        return $this->id && $this->wpTerm() !== null;
    }

    final protected function metaType(): string
    {
        return 'term';
    }
}
