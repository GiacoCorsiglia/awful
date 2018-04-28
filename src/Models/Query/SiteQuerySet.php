<?php
namespace Awful\Models\Query;

use ArrayAccess;
use Awful\Models\Database\BlockSet;
use Awful\Models\Database\BlockSetManager;
use Awful\Models\Database\Query\BlockQueryForSite;
use Awful\Models\Site;
use Countable;
use IteratorAggregate;
use WP_Site_Query;

class SiteQuerySet implements ArrayAccess, Countable, IteratorAggregate
{
    use QuerySetTrait;

    /** @var array */
    private $args;

    /** @var */
    private $wpSiteQuery;

    public function __construct(BlockSetManager $blockSetManager, array $args = [])
    {
        $this->blockSetManager = $blockSetManager;
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
        $this->wpSiteQuery = new WP_Site_Query($this->args);
        foreach ($this->wpSiteQuery->sites as $site) {
            $this->objects[$site->id] = new Site($this->blockSetForSite($site->id), $site->id);
        }

        return $this->objects;
    }

    public function wpSiteQuery(): WP_Site_Query
    {
        if ($this->objects === null) {
            $this->fetch();
        }
        return $this->wpSiteQuery;
    }

    public function fetchById(int $id): ?Site
    {
        $site = get_site($id);
        if (!$site) {
            return null;
        }

        return new Site($this->blockSetForSite($id), $id);
    }

    private function blockSetForSite(int $siteId): BlockSet
    {
        $blockSets = $this->blockSetManager->blockSetsForQuery(new BlockQueryForSite($siteId));
        return $blockSets[$siteId];
    }

    //
    // Filter methods.
    //

    public function fields(string $fields): self
    {
        assert($fields === 'ids');
        return $this->extend(['fields' => $fields]);
    }

    public function chunk(int $number, int $offset): self
    {
        return $this->extend([
            'number' => $number,
            'offset' => $offset,
        ]);
    }

    public function public(bool $public = true): self
    {
        return $this->extend(['public' => $public]);
    }

    public function archived(bool $archived = true): self
    {
        return $this->extend(['archived' => $archived]);
    }

    public function mature(bool $mature = true): self
    {
        return $this->extend(['mature' => $mature]);
    }

    public function spam(bool $spam = true): self
    {
        return $this->extend(['spam' => $spam]);
    }

    public function deleted(bool $deleted = true): self
    {
        return $this->extend(['deleted' => $deleted]);
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
        return new static($this->blockSetManager, $args + $this->args);
    }
}
