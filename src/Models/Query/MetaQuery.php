<?php
namespace Awful\Models\Query;

class MetaQuery
{
    public static function and(self ...$queries): self
    {
        $new = new self('', '');
        $new->relation = 'AND';
        $new->queries = $queries;
        return $new;
    }

    public static function or(self ...$queries): self
    {
        $new = new self('', '');
        $new->relation = 'OR';
        $new->queries = $queries;
        return $new;
    }

    /** @var array */
    private $args;

    /**
     * @var MetaQuery[]
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $queries;

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $relation;

    /**
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     */
    public function __construct(string $key, $value, string $compare = '=', string $type = 'CHAR')
    {
        $this->args = [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
            'type' => $type,
        ];
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
