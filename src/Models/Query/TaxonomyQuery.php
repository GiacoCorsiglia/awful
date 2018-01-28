<?php
namespace Awful\Query;

use Awful\Models\TaxonomyTerm;

class TaxonomyQuery extends Query
{
    /** @var array */
    protected $defaults = [
        'field' => 'term_id',
        'include_children' => true,
        'operator' => 'IN',
    ];

    /** @var array */
    protected $args;

    public function __construct(string $taxonomy, array $terms, array $options = [])
    {
        $_terms = [];
        foreach ($terms as $term) {
            $_terms[] = $term instanceof TaxonomyTerm ? $term->getId() : $term;
        }

        $this->args = [
            'taxonomy' => $taxonomy,
            'terms' => $_terms,
        ] + $options + $this->defaults;
    }

    public function toArray(): array
    {
        return $this->args;
    }
}
