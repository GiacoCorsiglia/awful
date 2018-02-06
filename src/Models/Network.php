<?php
namespace Awful\Models;

class Network extends ModelWithMetadata
{
    protected const OBJECT_TYPE = 'site';

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
