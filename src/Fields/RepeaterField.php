<?php
namespace Awful\Fields;

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

        assert(!empty(class_parents($this->row_class)[HasFieldsWithSource::class]), 'Expected row_class to be a subclass of HasFieldsWithSource');

        parent::__construct($args, $hooks);
    }

    public function toAcf(string $name, string $base_key = ''): array
    {
        $acf = parent::toAcf($name, $base_key);

        $row_class = $this->row_class;
        $new_base_key = $out['key'];

        $sub_fields = [];
        foreach ($row_class::getFields() as $sub_field_name => $sub_field) {
            $sub_fields[] = $sub_field->toAcf($sub_field_name, $new_base_key);
        }
        $acf['sub_fields'] = $sub_fields;

        // Keyify the field name in the "collapsed" option if its set.
        if (!empty($acf['collapsed'])) {
            $field['collapsed'] = $this->keyify($field['collapsed'], $new_base_key, true);
        }

        return $out;
    }

    public function toPhp($count, HasFields $owner, string $field_name): array
    {
        if (!$count || !is_int($count)) {
            return [];
        }

        // NOTE: assuming it's either HasFieldsWithSource or Model.
        if ($owner instanceof HasFieldsWithSource) {
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
