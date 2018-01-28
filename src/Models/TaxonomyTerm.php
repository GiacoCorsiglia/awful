<?php
namespace Awful\Models;

class TaxonomyTerm extends ModelWithMetadata
{
    const BUILTIN_FIELDS = [
        'term_id' => 'int',
        'name' => 'string',
        'slug' => 'string',
        'term_group' => 'int',
    ];
}
