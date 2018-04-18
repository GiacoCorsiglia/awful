<?php
namespace Awful\Models\Query;

use Awful\Models\TaxonomyTerm;

class TaxonomyQuery
{
    /** @var array */
    protected $defaults = [
        'field' => 'term_id',
        'include_children' => true,
        'operator' => 'IN',
    ];

    /** @var array */
    protected $args;

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $relation;

    /**
     * @var TaxonomyQuery[]
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $queries;

    public static function and(self ...$queries): self
    {
        $new = new self('', []);
        $new->relation = 'AND';
        $new->queries = $queries;
        return $new;
    }

    public static function or(self ...$queries): self
    {
        $new = new self('', []);
        $new->relation = 'OR';
        $new->queries = $queries;
        return $new;
    }

    public function __construct(string $taxonomy, array $terms, array $options = [])
    {
        $_terms = [];
        foreach ($terms as $term) {
            $_terms[] = $term instanceof TaxonomyTerm ? $term->id() : $term;
        }

        $this->args = [
            'taxonomy' => $taxonomy,
            'terms' => $_terms,
        ] + $options + $this->defaults;
    }

    public function toArray(): array
    {
        if (!$this->relation) {
            return $this->args;
        }

        $array = ['relation' => $this->relation];
        foreach ($this->queries as $query) {
            $array[] = $query->toArray();
        }
        return $array;
    }
}
