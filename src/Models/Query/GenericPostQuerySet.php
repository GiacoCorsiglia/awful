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

    public function fetch(): array
    {
        if ($this->objects !== null) {
            return $this->objects;
        }

        $this->objects = [];
        $this->wpQuery = new WP_Query($this->args);

        $ids = [];
        foreach ($this->wpQuery->posts as $wpPost) {
            $ids[] = $wpPost->ID;
            $this->objects[$wpPost->ID] = new GenericPost(
                $this->site,
                $wpPost->ID
            );
        }

        if ($this->args['awful_prefetch_blocks']) {
            $blockSetManager = $this->site->entityManager()->blockSetManager();
            $blockSetManager->prefetchBlockRecords(new BlockQueryForPosts($this->site->id(), ...$ids));
        }

        return $this->objects;
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

    public function ids(int ...$ids): array
    {
        return $this->filter(['post__in', $ids]);
    }

    public function metaQuery(MetaQuery $metaQuery): self
    {
        return $this->filter(['meta_query' => $metaQuery->toArray()]);
    }

    public function mimeType(string ...$mimeTypes): self
    {
        assert($this->args['post_type'] === 'attachment');
        return $this->filter(['post_mime_type' => $mimeTypes]);
    }

    public function orderBy(string ...$orders): self
    {
        $orderArg = [];
        $orderByArg = [];
        foreach ($orders as $order) {
            if (!$order) {
                continue;
            }
            if ($order[0] === '-') {
                $order = substr($order, 1);
                $orderArg[] = 'ASC';
            } else {
                $orderArg[] = 'DESC';
            }
            $orderByArg[] = $order;
        }

        return $this->filter([
            'order' => $orderArg,
            'orderby' => $orderByArg,
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

    public function taxonomyQuery(TaxonomyQuery $taxQuery): self
    {
        return $this->filter(['tax_query' => $taxQuery->toArray()]);
    }

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

    protected function defaults(): array
    {
        return [
            // SEE: https://codex.wordpress.org/Class_Reference/WP_Query#Type_Parameters
            'post_type' => 'any',

            'awful_prefetch_blocks' => true,
        ];
    }
}
