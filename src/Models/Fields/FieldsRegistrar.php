<?php
namespace Awful\Models\Fields;

interface FieldsRegistrar
{
    public function registerPostTypeFields(string $post_subclass): void;

    public function registerTaxonomyTermFields(string $taxonomy_term_subclass): void;

    // TODO: Options pages.
    // public function registerOptionsPage(string $options_page_subclass): void;
}
