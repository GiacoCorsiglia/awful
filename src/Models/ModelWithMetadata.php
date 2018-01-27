<?php
namespace Awful\Models;

use Awful\Fields\HasFields;

abstract class ModelWithMetadata extends Model
{
    protected $data;

    protected function fetchData()
    {
        if (!$this->isSaved()) {
            return;
        }

        $get_meta = 'get_' . static::OBJECT_TYPE . '_meta';

        $metadata = $get_meta($this->id);
        foreach ($metadata as $key => $value) {
            if (isset($value[0])) {
                // WordPress supports multiple meta values per key.  If
                // multiple exist, we'll let this be an array of each of the
                // values.  But if only one exists, we'll collapse it down
                // so the $key points directly to the single value.
                $this->data[$key] = isset($value[1]) ? $value : $value[0];
            }
        }
    }

    public function update(array $data): HasFields
    {
        // TODO
    }

    public function delete(string ...$keys): HasFields
    {
        // TODO
    }
}
