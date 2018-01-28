<?php
namespace Awful\Query;

final class Relation extends Query
{
    /** @var string */
    private $relation;

    /** @var Query[] */
    private $queries;

    private function __construct(string $relation, array $queries)
    {
        $this->relation = $relation;
        $this->queries = $queries;
    }

    public static function and(Query ...$queries): self
    {
        return new self('AND', $queries);
    }

    public static function or(Query ...$queries): self
    {
        return new self('OR', $queries);
    }

    public function toArray(): array
    {
        $array = ['relation' => $this->relation];
        foreach ($this->queries as $query) {
            $array[] = $query->toArray();
        }
        return $array;
    }
}
