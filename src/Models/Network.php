<?php
namespace Awful\Models;

class Network extends Model
{
    /**
     * wp_site
     * wp_sitemeta.
     */
    const BUILTIN_FIELDS = [
        'id' => 'int',
        'domain' => 'string',
        'path' => 'string',
    ];
}
