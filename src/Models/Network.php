<?php
namespace Awful\Models;

use Awful\Models\Fields\FieldsResolver;
use Awful\Models\Traits\ModelWithMetaTable;

/**
 * @todo
 */
class Network extends Model
{
    protected const OBJECT_TYPE = 'site';

    /**
     * wp_site
     * wp_sitemeta.
     */
    const WORDPRESS_OBJECT_FIELDS = [
        'id' => 'int',
        'domain' => 'string',
        'path' => 'string',
    ];

    protected function __construct(
        int $id = 0,
        FieldsResolver $resolver = null
    ) {
        assert(is_multisite(), 'Instantiating a Network only makes sense for multisite installs');

        $this->id = $id;

        $this->initializeFieldsResolver($resolver);
    }
}
