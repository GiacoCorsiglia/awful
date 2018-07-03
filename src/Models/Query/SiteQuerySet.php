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

    /** @var array */
    private $args;

    /** @var EntityManager */
    private $entityManager;

    /** @var */
    private $wpSiteQuery;

    public function __construct(EntityManager $entityManager, array $args = [])
    {
        $this->entityManager = $entityManager;
        $this->args = $args + $this->defaults();
    }

    public function archived(bool $archived = true): self
    {
        return $this->extend(['archived' => $archived]);
    }

    public function chunk(int $number, int $offset): self
    {
        return $this->extend([
            'number' => $number,
            'offset' => $offset,
        ]);
    }

    public function deleted(bool $deleted = true): self
    {
        return $this->extend(['deleted' => $deleted]);
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
        $this->wpSiteQuery = new WP_Site_Query($this->args);
        foreach ($this->wpSiteQuery->sites as $site) {
            $this->objects[$site->id] = new Site($this->entityManager, $site->id);
        }

        return $this->objects;
    }

    public function fetchById(int $id): ?Site
    {
        $site = get_site($id);
        if (!$site) {
            return null;
        }

        return new Site($this->entityManager, $id);
    }

    //
    // Filter methods.
    //

    public function fields(string $fields): self
    {
        assert($fields === 'ids');
        return $this->extend(['fields' => $fields]);
    }

    public function mature(bool $mature = true): self
    {
        return $this->extend(['mature' => $mature]);
    }

    public function public(bool $public = true): self
    {
        return $this->extend(['public' => $public]);
    }

    public function spam(bool $spam = true): self
    {
        return $this->extend(['spam' => $spam]);
    }

    public function wpSiteQuery(): WP_Site_Query
    {
        if ($this->objects === null) {
            $this->fetch();
        }
        return $this->wpSiteQuery;
    }

    //
    // Internal methods.
    //

    protected function defaults(): array
    {
        return [
            'number' => 0,
        ];
    }

    protected function extend(array $args): self
    {
        return new static($this->entityManager, $args + $this->args);
    }
}
