<?php
namespace Awful\Models\Traits;

use Awful\Models\BlockSet;
use Awful\Models\Site;

trait ModelOwnedBySite
{
    use ModelWithSiteContext;
    use BlockOwner;

    /** @var int */
    private $id;

    /** @var Site */
    private $site;

    public function __construct(
        Site $site,
        int $id = 0,
        BlockSet $blockSet = null
    ) {
        $this->site = $site;
        $this->siteId = $site->id();
        $this->id = $id;
        $this->initializeBlockSet($blockSet ?: new BlockSet([]));
    }

    final public function id(): int
    {
        return $this->id;
    }

    final public function site(): Site
    {
        return $this->site;
    }
}
