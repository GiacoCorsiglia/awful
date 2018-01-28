<?php
namespace Awful\Query;

class MetaQuery extends Query
{
    /** @var array */
    protected $args;

    /**
     * Undocumented function.
     * @param string $key
     * @param mixed  $value
     * @param string $compare
     * @param string $type
     */
    public function __construct(string $key, $value, string $compare = '=', string $type = 'CHAR')
    {
        $this->args = [
            'key' => $taxonomy,
            'value' => $value,
            'compare' => $compare,
            'type' => $type,
        ];
    }

    public function toArray(): array
    {
        return $this->args;
    }
}
