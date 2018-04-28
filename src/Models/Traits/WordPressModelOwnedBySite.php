<?php
namespace Awful\Models\Traits;

use Awful\Models\Database\BlockSet;
use Awful\Models\Site;

/**
 * For WordPress objects that are the child of a specific Site (or "blog") in
 * WordPress multisite.
 *
 * Post, Term, and Comment.
 */
trait WordPressModelOwnedBySite
{
    use WordPressModelWithSiteContext;

    /** @var int */
    private $id;

    /** @var Site */
    private $site;

    public function __construct(
        Site $site,
        BlockSet $blockSet,
        int $id = 0
    ) {
        $this->site = $site;
        // Set the `siteId` for WordPressModelWithSiteContext
        $this->siteId = $site->id();
        $this->initializeBlockSet($blockSet);
        $this->id = $id;
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
