<?php
namespace Awful\Models\Query;

use ArrayAccess;
use ArrayIterator;
use Awful\Exceptions\ImmutabilityException;
use Awful\Models\Site;
use Countable;
use IteratorAggregate;
use WP_Post;
use WP_Query;

class PostQuerySet implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var Site */
    protected $site;

    /** @var array */
    protected $args;

    /**
     * @var \WP_Query
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $wp_query;

    /**
     * @var array
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $posts;

    /** @var bool */
    protected $has_fetched = false;

    public function __construct(Site $site, array $args = [])
    {
        $this->site = $site;

        $this->args = $args + $this->defaults();
    }

    public function getIterator(): ArrayIterator
    {
        if (!$this->has_fetched) {
            $this->fetch();
        }
        return new ArrayIterator($this->posts);
    }

    public function count(): int
    {
        if (!$this->has_fetched) {
            $this->fetch();
        }
        return count($this->posts);
    }

    public function offsetExists(int $post_id): bool
    {
        if (!$this->has_fetched) {
            $this->fetch();
        }
        return isset($this->posts[$post_id]);
    }

    public function offsetGet(int $post_id): ?WP_Post
    {
        if (!$this->has_fetched) {
            $this->fetch();
        }
        return $this->posts[$post_id] ?? null;
    }

    public function offsetSet(int $offset, WP_Post $value): void
    {
        throw new ImmutabilityException();
    }

    public function offsetUnset(int $offset): void
    {
        throw new ImmutabilityException();
    }

    public function any(): bool
    {
        return (bool) $this->count();
    }

    public function toArray(bool $associative = true): array
    {
        if (!$this->has_fetched) {
            $this->fetch();
        }
        return $associative ? $this->posts : array_values($this->posts);
    }

    public function toWpQuery(): WP_Query
    {
        if (!$this->has_fetched) {
            $this->fetch();
        }
        return $this->wp_query;
    }

    public function type(string ...$types): self
    {
        return $this->extend(['post_type' => $types]);
    }

    public function status(string ...$statuses): self
    {
        return $this->extend(['post_status' => $statuses]);
    }

    public function search(string $keyword, bool $excludes = false): self
    {
        if ($excludes) {
            $keyword = "-$keyword";
        }
        return $this->extend(['s' => $keyword]);
    }

    /**
     * @param  bool|string $value
     * @return self
     */
    public function password($value = true): self
    {
        if (is_string($value)) {
            return $this->extend([
                'has_password' => null,
                'post_password' => $value,
            ]);
        }
        return $this->extend(['has_password' => $value]);
    }

    public function orderBy(string ...$orders): self
    {
        $order_arg = [];
        $order_by_arg = [];
        foreach ($orders as $order) {
            if (!$order) {
                continue;
            }
            if ($order[0] === '-') {
                $order = substr($order, 1);
                $order_arg[] = 'ASC';
            } else {
                $order_arg[] = 'DESC';
            }
            $order_by_arg[] = $order;
        }

        return $this->extend([
            'order' => $order_arg,
            'orderby' => $order_by_arg,
        ]);
    }

    public function metaQuery(MetaQuery $meta_query): self
    {
        return $this->extend(['meta_query' => $meta_query->toArray()]);
    }

    public function taxonomyQuery(TaxonomyQuery $tax_query): self
    {
        return $this->extend(['tax_query' => $tax_query->toArray()]);
    }

    public function fields(string $fields): self
    {
        assert(in_array($fields, ['ids', 'id=>parent']));
        return $this->extend(['fields' => $fields]);
    }

    public function currentUserHasPermission(string $permission): self
    {
        return $this->extend(['perm' => $permission]);
    }

    public function mimeType(string ...$mime_types): self
    {
        assert($this->args['post_type'] === 'attachment');
        return $this->extend(['post_mime_type' => $mime_types]);
    }

    protected function fetch(): WP_Query
    {
        $this->has_fetched = true;
        $this->wp_query = new WP_Query($this->args);
        $this->posts = [];
        foreach ($this->wp_query->posts as $wp_post) {
            $this->posts[$wp_post->ID] = $wp_post;
        }
        return $this->wp_query;
    }

    protected function defaults(): array
    {
        return [
            // SEE: https://codex.wordpress.org/Class_Reference/WP_Query#Type_Parameters
            'post_type' => 'any',
        ];
    }

    protected function extend(array $args): self
    {
        if ($this->has_fetched) {
            throw new \BadMethodCallException('Cannot mutate PostQuerySet after fetch.');
        }
        return new static($this->site, $args + $this->args);
    }
}
