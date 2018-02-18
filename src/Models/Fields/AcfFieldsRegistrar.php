<?php
namespace Awful\Models\Fields;

use Awful\Models\GenericPost;
use Awful\Models\HasFields;
use Awful\Models\TaxonomyTerm;

class AcfFieldsRegistrar implements FieldsRegistrar
{
    /**
     * Field sets that were registered before ACF initialized.
     *
     * @var array
     */
    private $deferred = [];

    /**
     * Whether the 'acf/init' action has been run yet.
     *
     * @var bool
     */
    private $has_acf_init = false;

    /** @var FieldsResolver */
    private $resolver;

    /**
     * @param FieldsResolver $resolver
     */
    public function __construct(FieldsResolver $resolver)
    {
        $this->resolver = $resolver;

        // NOTE: This assumes the object will be constructed before 'acf/init'.
        add_action('acf/init', function (): void {
            $this->has_acf_init = true;
            foreach ($this->deferred as $deferred_args) {
                $this->register(...$deferred_args);
            }
        });
    }

    public function registerPostTypeFields(string $post_subclass): void
    {
        assert(is_subclass_of($post_subclass, GenericPost::class), 'Expected `GenericPost` subclass');

        $this->register($post_subclass, [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => $post_subclass::TYPE,
        ]]]);
    }

    public function registerTaxonomyTermFields(string $taxonomy_term_subclass): void
    {
        assert(is_subclass_of($taxonomy_term_subclass, TaxonomyTerm::class), 'Expected `TaxonomyTerm` subclass');

        $this->register($taxonomy_term_subclass, [[[
            'param' => '',
            'operator' => '==',
            'value' => $taxonomy_term_subclass::TYPE,
        ]]]);
    }

    private function register(string $field_set_class, array $location): void
    {
        assert(is_subclass_of($field_set_class, HasFields::class), 'Expected `HasFields` subclass');

        if (!$this->has_acf_init) {
            $this->deferred[] = [$field_set_class, $location];
            return;
        }

        $key = strtr($field_set_class, '\\', '_');

        /** @psalm-suppress UndefinedFunction */
        acf_add_local_field_group([
            'key' => "group_$key",
            'title' => static::FIELD_GROUP_TITLE,
            'fields' => $this->resolver->resolve($field_set_class),
            'location' => $location,
            'position' => static::FIELD_GROUP_POSITION,
            'style' => static::FIELD_GROUP_STYLE,
            'hide_on_screen' => static::FIELD_GROUP_HIDE_ON_SCREEN,
        ]);
    }
}
