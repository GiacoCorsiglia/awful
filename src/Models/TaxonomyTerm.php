<?php
namespace Awful\Models;

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
}
