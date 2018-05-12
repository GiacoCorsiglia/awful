<?php
namespace Awful\Models\Traits;

use Awful\Models\Comment;
use Awful\Models\Database\EntityManager;
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

    final public function __construct(
        Site $site,
        int $id = 0
    ) {
        $this->site = $site;
        // Set the `siteId` for WordPressModelWithSiteContext
        $this->siteId = $site->id();
        $this->id = $id;
    }

    final public function entityManager(): EntityManager
    {
        return $this->site->entityManager();
    }

    final public function blockRecordColumnValue(): int
    {
        return $this->id;
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
