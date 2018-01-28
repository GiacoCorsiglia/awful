<?php
namespace Awful\Models\Fields;

use Awful\Models\SubModel;

// TODO: Allow adding/modifying rows with code
class RepeaterField extends Field
{
    const ACF_TYPE = 'repeater';

    /** @var string */
    private $row_class;

    public function __construct(array $args = [], array $hooks = [])
    {
        assert(!empty($args['row_class']), 'Expected row_class');

        $this->row_class = $args['row_class'];
        // Unset it since ACF doesn't need this.
        unset($args['row_class']);

        assert(!empty(class_parents($this->row_class)[SubModel::class]), 'Expected row_class to be a subclass of SubModel');

        parent::__construct($args, $hooks);
    }

    public function toAcf(string $name, string $base_key, FieldsResolver $resolver): array
    {
        $acf = parent::toAcf($name, $base_key);

        $row_class = $this->row_class;
        $new_base_key = $this->buildAcfKey($name, $base_key, false);

        $sub_fields = [];
        foreach ($resolver->resolve($row_class) as $sub_field_name => $sub_field) {
            $sub_fields[] = $sub_field->toAcf($sub_field_name, $new_base_key);
        }
        $acf['sub_fields'] = $sub_fields;

        // Keyify the field name in the "collapsed" option if its set.
        if (!empty($acf['collapsed'])) {
            $field['collapsed'] = $this->buildAcfKey($field['collapsed'], $new_base_key, true);
        }

        return $acf;
    }

    public function forPhp($count, HasFields $owner, string $field_name): array
    {
        if (!$count || !is_int($count)) {
            return [];
        }

        // NOTE: assuming it's either SubModel or Model.
        // TODO: Share with FlexibleContentField.
        if ($owner instanceof SubModel) {
            $source = $owner->getDataSource();
            $prefix = $owner->getDataPrefix();
        } else {
            $source = $owner;
            $prefix = '';
        }

        $row_class = $this->row_class;

        $rows = [];
        for ($index = 0; $index < $count; $index++) {
            $rows[] = new $row_class($source, "${prefix}${field_name}_${index}_");
        }
        return $rows;
    }
}
