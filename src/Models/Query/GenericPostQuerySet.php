<?php
namespace Awful\Models\Query;

use ArrayAccess;
use Awful\Models\Database\Query\BlockQueryForPosts;
use Awful\Models\GenericPost;
use Awful\Models\Site;
use Countable;
use IteratorAggregate;
use WP_Query;

class GenericPostQuerySet implements ArrayAccess, Countable, IteratorAggregate
{
    use QuerySetTrait;

    /** @var Site */
    protected $site;

    /** @var array */
    protected $args;

    /** @var WP_Query|null */
    protected $wpQuery;

    /** @var GenericPost[]|null */
    protected $posts;

    public function __construct(Site $site, array $args = [])
    {
        $this->site = $site;
        $this->blockSetManager = $site->blockSet()->manager();
        $this->args = $args + $this->defaults();
    }

    //
    // Fetch methods.
    //

    public function fetch(): array
    {
        if ($this->objects !== null) {
            return $this->objects;
        }

        $this->objects = [];
        $this->wpQuery = new WP_Query($this->args);

        $ids = wp_list_pluck($this->wpQuery->posts, 'ID');
        $blockSets = $this->blockSetManager->blockSetsForQuery(new BlockQueryForPosts($this->site->id(), ...$ids));
        foreach ($this->wpQuery->posts as $wpPost) {
            $this->posts[$wpPost->ID] = new GenericPost(
                $this->site,
                $blockSets[$wpPost->ID],
                $wpPost->ID
            );
        }

        return $this->objects;
    }

    public function wpQuery(): WP_Query
    {
        if ($this->objects === null) {
            $this->fetch();
        }
        /** @psalm-var WP_Query */
        return $this->wpQuery;
    }

    public function fetchByIds(int ...$ids): array
    {
        $this->extend(['post__in', $ids]);
        return $this->fetch();
    }

    //
    // Filter methods.
    //

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

    //
    // Internal methods.
    //

    protected function defaults(): array
    {
        return [
            // SEE: https://codex.wordpress.org/Class_Reference/WP_Query#Type_Parameters
            'post_type' => 'any',
        ];
    }

    protected function extend(array $args): self
    {
        if ($this->objects !== null) {
            throw new \BadMethodCallException('Cannot mutate PostQuerySet after fetch.');
        }
        return new static($this->site, $args + $this->args);
    }
}
