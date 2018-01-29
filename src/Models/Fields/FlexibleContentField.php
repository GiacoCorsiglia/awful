<?php
namespace Awful\Models\Fields;

use Awful\Models\HasFields;
use Awful\Models\SubModel;
use function Awful\every;
use function Awful\is_associative;

// TODO: Support adding/modifying layouts by code.
class FlexibleContentField extends Field
{
    const ACF_TYPE = 'flexible_content';

    /**
     * Map from layout field name to corresponding PHP class.
     *
     * @var string[]
     */
    private $layout_classes;

    public function __construct(array $args = [], array $hooks = [])
    {
        assert(!empty($args['layout_classes']), 'Expected layout_classes');
        assert(is_associative($args['layout_classes']), 'Expected associative array of layout classes');
        assert(every($args['layout_classes'], 'is_subclass_of', [SubModel::class]), 'Expected each layout class to be a subclass of SubModel');

        $this->layout_classes = $args['layout_classes'];
        unset($args['layout_classes']);

        parent::__construct($args, $hooks);
    }

    public function toAcf(string $field_name, string $base_key, FieldsResolver $resolver): array
    {
        $acf = parent::toAcf($field_name, $base_key, $resolver);

        $new_base_key = $this->buildAcfKey($field_name, $base_key, false);
        $layouts = [];
        foreach ($this->layout_classes as $layout_name => $layout_class) {
            $layout_key = $this->buildAcfKey($layout_name, $new_base_key, false);

            $sub_fields = [];
            foreach ($resolver->resolve($layout_class) as $sub_field_name => $sub_field) {
                $sub_fields[] = $sub_field->toAcf($sub_field_name, $layout_key, $resolver);
            }

            $layouts[] = [
                'key' => $layout_key,
                'name' => $layout_name,
                'label' => defined("$layout_class::LABEL") ? $layout_class::LABEL : '',
                'display' => defined("$layout_class::DISPLAY") ? $layout_class::DISPLAY : '',
                'sub_fields' => $sub_fields,
            ];
        }

        $acf['layouts'] = $layouts;

        return $acf;
    }

    /**
     * Gets the title for the current layout in the backend so it can be customized
     * based on the layout content.
     *
     * Here we just call in to the appropriate layout class so each different
     * layout type can contain its own logic.
     * @see https://www.advancedcustomfields.com/resources/acf-fields-flexible_content-layout_title/
     *
     * @param string $title
     * @param array  $field
     * @param array  $layout
     * @param int    $i
     *
     * @return string The title for the current layout
     */
    public function getLayoutTitle($title, $field, $layout, $i): string
    {
        $layout_class = $this->layout_classes[$layout['name']] ?? '';
        if ($layout_class && method_exists($layout_class, 'getLayoutTitle')) {
            return $layout_class::getLayoutTitle($title, $i);
        }

        return $title;
    }

    public function forPhp($layout_names, HasFields $owner, string $field_name): array
    {
        if (!$layout_names || !is_array($layout_names)) {
            return [];
        }

        // NOTE: assuming it's either SubModel or Model.
        // TODO: Share with Repeater, maybe.
        if ($owner instanceof SubModel) {
            $source = $owner->getDataSource();
            $prefix = $owner->getDataPrefix();
        } else {
            $source = $owner;
            $prefix = '';
        }

        $layouts = [];
        foreach ($layout_names as $index => $layout_name) {
            $layout_class = $this->layout_classes[$layout_name] ?? '';

            if (!$layout_class) {
                // Skips it if it's an invalid layout name; i.e., one that isn't
                // mapped to any layout class.  Could be developer error, so...
                // TODO: Notify developer in this case, even on production?
                continue;
            }

            $layouts[] = new $layout_class($source, "${prefix}${field_name}_{$index}_", $owner->getFieldsResolver());
        }
        return $layouts;
    }
}
