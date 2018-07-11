<?php
namespace Awful\Models\Query;

use ArrayAccess;
use Awful\Models\Database\EntityManager;
use Awful\Models\Site;
use Countable;
use IteratorAggregate;
use WP_Site_Query;

class SiteQuerySet implements ArrayAccess, Countable, IteratorAggregate
{
    use QuerySetTrait;

    /**
     * @var mixed[]
     * @psalm-var array<string, mixed>
     */
    private $args;

    /** @var EntityManager */
    private $entityManager;

    /** @var WP_Site_Query|null */
    private $wpSiteQuery;

    /**
     * @param EntityManager $entityManager
     * @param mixed[] $args
     * @psalm-param array<string, mixed> $args
     */
    public function __construct(EntityManager $entityManager, array $args = [])
    {
        assert(is_multisite());

        $this->entityManager = $entityManager;
        $this->args = $args + $this->defaults();
    }

    /**
     * @param bool $archived
     * @return static
     */
    public function archived(bool $archived = true): self
    {
        return $this->filter(['archived' => $archived]);
    }

    /**
     * @param int $number
     * @param int $offset
     * @return static
     */
    public function chunk(int $number, int $offset): self
    {
        return $this->filter([
            'number' => $number,
            'offset' => $offset,
        ]);
    }

    /**
     * @param bool $deleted
     * @return static
     */
    public function deleted(bool $deleted = true): self
    {
        return $this->filter(['deleted' => $deleted]);
    }

    /**
     * @return Site[] Array of Sites keyed by ID.
     * @psalm-return array<int, Site>
     */
    public function fetch(): array
    {
        if ($this->objects !== null) {
            /** @psalm-var array<int, Site> */
            return $this->objects;
        }

        $this->objects = [];
        $this->wpSiteQuery = new WP_Site_Query($this->args);
        foreach ($this->wpSiteQuery->sites as $site) {
            $this->objects[$site->id] = new Site($this->entityManager, $site->id);
        }

        /** @psalm-var array<int, Site> */
        return $this->objects;
    }

    /**
     * @param int $id
     * @return Site|null
     */
    public function fetchById(int $id): ?Site
    {
        $site = get_site($id);
        if (!$site) {
            return null;
        }

        return new Site($this->entityManager, $id);
    }

    /**
     * @param mixed[] $args
     * @psalm-param array<string, mixed> $args
     * @return static
     */
    public function filter(array $args): self
    {
        return new static($this->entityManager, $args + $this->args);
    }

    /**
     * @return int[]
     * @psalm-return array<int, int>
     */
    public function ids(): array
    {
        $this->wpSiteQuery = new WP_Site_Query($this->args + ['fields' => 'ids']);
        /** @psalm-var array<int, int> */
        return $this->wpSiteQuery->sites;
    }

    /**
     * @param bool $mature
     * @return static
     */
    public function mature(bool $mature = true): self
    {
        return $this->filter(['mature' => $mature]);
    }

    /**
     * @param bool $public
     * @return static
     */
    public function public(bool $public = true): self
    {
        return $this->filter(['public' => $public]);
    }

    /**
     * @param bool $spam
     * @return static
     */
    public function spam(bool $spam = true): self
    {
        return $this->filter(['spam' => $spam]);
    }

    /**
     * @return WP_Site_Query
     */
    public function wpSiteQuery(): WP_Site_Query
    {
        if ($this->wpSiteQuery === null) {
            $this->fetch();
        }
        /** @psalm-var WP_Site_Query We know it isn't null now. */
        return $this->wpSiteQuery;
    }

    /**
     * @return mixed[]
     * @psalm-return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'number' => 0,
        ];
    }
}
