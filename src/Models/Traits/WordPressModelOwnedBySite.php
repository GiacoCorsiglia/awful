<?php
namespace Awful\Models\Traits;

use Awful\Models\Comment;
use Awful\Models\Database\BlockSet;
use Awful\Models\Database\Query\BlockOwnerIdForComment;
use Awful\Models\Database\Query\BlockOwnerIdForPost;
use Awful\Models\Database\Query\BlockOwnerIdForTerm;
use Awful\Models\GenericPost;
use Awful\Models\Site;
use Awful\Models\TaxonomyTerm;

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

    final public function __construct(Site $site, BlockSet $blockSet)
    {
        $ownerId = $blockSet->ownerId();
        assert(
            ($this instanceof GenericPost && $ownerId instanceof BlockOwnerIdForPost)
            || ($this instanceof Comment && $ownerId instanceof BlockOwnerIdForComment)
            || ($this instanceof TaxonomyTerm && $ownerId instanceof BlockOwnerIdForTerm)
        );
        assert($ownerId->siteId() === $site->id());

        $this->site = $site;
        // Set the `siteId` for WordPressModelWithSiteContext
        $this->siteId = $site->id();
        $this->initializeBlockSet($blockSet);
        $this->id = $ownerId->value();
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
