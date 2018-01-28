<?php
namespace Awful\Models;

class Site extends Model
{
    /**
     * Only apply when is_multisite().
     */
    const BUILTIN_FIELDS = [
        'blog_id' => 'int',
        'site_id' => 'int',
        'domain' => 'string',
        'path' => 'string',
        'registered' => 'date',
        'last_updated' => 'date',
        'public' => 'int',
        'archived' => 'int',
        'mature' => 'int',
        'spam' => 'int',
        'deleted' => 'int',
        'lang_id' => 'int',
    ];
}
