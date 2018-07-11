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

    /** @var array */
    protected $args;

    /** @var GenericPost[]|null */
    protected $posts;

    /** @var Site */
    protected $site;

    /** @var WP_Query|null */
    protected $wpQuery;

    public function __construct(Site $site, array $args = [])
    {
        $this->site = $site;
        $this->args = $args + $this->defaults();
    }

    public function currentUserHasPermission(string $permission): self
    {
        return $this->filter(['perm' => $permission]);
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

        if ($this->args['awful_prefetch_blocks']) {
            $blockSetManager = $this->site->entityManager()->blockSetManager();
            $blockSetManager->prefetchBlockRecords(new BlockQueryForPosts($this->site->id(), ...$ids));
        }

        foreach ($this->wpQuery->posts as $wpPost) {
            $this->posts[$wpPost->ID] = new GenericPost(
                $this->site,
                $wpPost->ID
            );
        }

        return $this->objects;
    }

    public function fetchByIds(int ...$ids): array
    {
        return $this->filter(['post__in', $ids])->fetch();
    }

    public function fields(string $fields): self
    {
        assert(in_array($fields, ['ids', 'id=>parent']));
        return $this->filter(['fields' => $fields]);
    }

    public function filter(array $args): self
    {
        return new static($this->site, $args + $this->args);
    }

    public function metaQuery(MetaQuery $meta_query): self
    {
        return $this->filter(['meta_query' => $meta_query->toArray()]);
    }

    public function mimeType(string ...$mime_types): self
    {
        assert($this->args['post_type'] === 'attachment');
        return $this->filter(['post_mime_type' => $mime_types]);
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

        return $this->filter([
            'order' => $order_arg,
            'orderby' => $order_by_arg,
        ]);
    }

    /**
     * @param bool|string $value
     *
     * @return self
     */
    public function password($value = true): self
    {
        if (is_string($value)) {
            return $this->filter([
                'has_password' => null,
                'post_password' => $value,
            ]);
        }
        return $this->filter(['has_password' => $value]);
    }

    public function search(string $keyword, bool $excludes = false): self
    {
        if ($excludes) {
            $keyword = "-$keyword";
        }
        return $this->filter(['s' => $keyword]);
    }

    public function status(string ...$statuses): self
    {
        return $this->filter(['post_status' => $statuses]);
    }

    public function taxonomyQuery(TaxonomyQuery $tax_query): self
    {
        return $this->filter(['tax_query' => $tax_query->toArray()]);
    }

    //
    // Filter methods.
    //

    public function type(string ...$types): self
    {
        return $this->filter(['post_type' => $types]);
    }

    public function wpQuery(): WP_Query
    {
        if ($this->objects === null) {
            $this->fetch();
        }
        /** @psalm-var WP_Query */
        return $this->wpQuery;
    }

    //
    // Internal methods.
    //

    protected function defaults(): array
    {
        return [
            // SEE: https://codex.wordpress.org/Class_Reference/WP_Query#Type_Parameters
            'post_type' => 'any',

            'awful_prefetch_blocks' => true,
        ];
    }
}
