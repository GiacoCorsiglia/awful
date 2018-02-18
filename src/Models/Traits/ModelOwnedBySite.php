<?php
namespace Awful\Models\Traits;

use Awful\Models\Fields\FieldsResolver;

trait ModelOwnedBySite
{
    use ModelWithSiteContext;

    protected function __construct(
        int $id = 0,
        int $site_id = 0,
        FieldsResolver $resolver = null
    ) {
        $this->id = $id;
        $this->site_id = is_multisite() ? $site_id : 0;

        $this->initializeFieldsResolver($resolver);
    }
}
