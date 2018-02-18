<?php
namespace Awful\Models;

use Awful\Models\Traits\ModelOwnedBySite;
use Awful\Models\Traits\ModelWithMetaTable;

class TaxonomyTerm extends Model
{
    use ModelOwnedBySite;
    use ModelWithMetaTable;

    const WORDPRESS_OBJECT_FIELDS = [
        'term_id' => 'int',
        'name' => 'string',
        'slug' => 'string',
        'term_group' => 'int',
    ];

    final protected function getMetaType(): string
    {
        return 'term';
    }

    final public function exists(): bool
    {
        // Todo
        return true;
    }
}
