<?php
namespace Awful\Models\Query;

class MetaQuery
{
    /** @var array */
    private $args;

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $relation;

    /**
     * @var MetaQuery[]
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $queries;

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
