<?php
namespace Awful\Models\Fields;

use Awful\Models\HasFields;
use Awful\Models\SubModel;

// TODO: Allow adding/modifying rows with code
class RepeaterField extends Field
{
    const ACF_TYPE = 'repeater';

    /** @var string */
    private $row_class;

    public function __construct(array $args = [])
    {
        assert(!empty($args['row_class']), 'Expected row_class');

        $this->row_class = $args['row_class'];
        // Unset it since ACF doesn't need this.
        unset($args['row_class']);

        assert(!empty(class_parents($this->row_class)[SubModel::class]), 'Expected row_class to be a subclass of SubModel');

        parent::__construct($args);
    }

    public function toAcf(string $name, string $base_key, FieldsResolver $resolver): array
    {
        $acf = parent::toAcf($name, $base_key, $resolver);

        $row_class = $this->row_class;
        $new_base_key = $this->buildAcfKey($name, $base_key, false);

        $sub_fields = [];
        foreach ($resolver->resolve($row_class) as $sub_field_name => $sub_field) {
            $sub_fields[] = $sub_field->toAcf($sub_field_name, $new_base_key, $resolver);
        }
        $acf['sub_fields'] = $sub_fields;

        // Keyify the field name in the "collapsed" option if its set.
        if (!empty($acf['collapsed'])) {
            $acf['collapsed'] = $this->buildAcfKey($acf['collapsed'], $new_base_key, true);
        }

        return $acf;
    }

    public function forPhp($count, HasFields $owner, string $field_name): array
    {
        // The `$count` might come in as a string---e.g., '3'.
        // TODO: This returns `1` for a non-empty array.
        $count = (int) $count;

        if (!$count) {
            // An int cast on a weird string will return 0.
            return [];
        }

        $rows = [];
        $source = $owner->getDataSource();
        $prefix = $owner->getDataPrefix();
        $row_class = $this->row_class;
        for ($index = 0; $index < $count; $index++) {
            $rows[] = new $row_class($source, "${prefix}${field_name}_${index}_", $owner->getFieldsResolver());
        }
        return $rows;
    }
}
