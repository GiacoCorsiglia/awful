<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Exceptions\SiteMismatchException;
use Awful\Models\Database\Query\BlockQuery;
use Awful\Models\Database\Query\BlockQueryForSite;

class BlockSetManager
{
    private const CACHE_GROUP_PREFIX = 'awful_';

    /** @var Database */
    private $db;

    /** @var BlockTypeMap */
    private $blockTypeMap;

    public function __construct(Database $db, BlockTypeMap $blockTypeMap)
    {
        $this->db = $db;
        $this->blockTypeMap = $blockTypeMap;

        wp_cache_add_global_groups([
            self::CACHE_GROUP_PREFIX . Database::SITE_COLUMN,
            self::CACHE_GROUP_PREFIX . Database::USER_COLUMN,
        ]);
    }

    public function blockTypeMap(): BlockTypeMap
    {
        return $this->blockTypeMap;
    }

    public function blockSetsForQuery(BlockQuery $blockQuery): array
    {
        if ($blockQuery instanceof BlockQueryForSite) {
            return $this->blockSetForSite($blockQuery);
        }

        $siteId = $blockQuery->siteId();

        // Switch to the necessary site for wp_cache_* calls (at least for those
        // that aren't in a global cache group).
        $switched = $siteId && get_current_blog_id() !== $siteId;
        if ($switched) {
            switch_to_blog($siteId);
        }

        $ids = $blockQuery->ids();

        $column = $blockQuery->column();

        $cacheGroup = self::CACHE_GROUP_PREFIX . $blockQuery->column();

        $result = [];

        $cachedIds = [];
        foreach ($ids as $id) {
            $cachedBlocks = wp_cache_get((string) $id, $cacheGroup);

            // `false` indicates that the value isn't in the cache; we will
            // however store empty arrays of blocks in the cache.
            if ($cachedBlocks !== false) {
                $cachedIds = $ids;
                $result[$id] = $cachedBlocks;
            }
        }

        $uncachedBlockQuery = $blockQuery->without($cachedIds);
        if ($uncachedBlockQuery->any()) {
            foreach ($this->db->fetchBlocks($uncachedBlockQuery) as $block) {
                $id = $block->$column;
                if (!isset($result[$id])) {
                    $result[$id] = [];
                }
                $result[$id][] = $block;
            }

            foreach ($uncachedBlockQuery->values() as $id) {
                if (!isset($result[$id])) {
                    // We will create empty BlockSets for any ids that don't
                    // have any blocks saved.
                    $result[$id] = [];
                }
                wp_cache_set((string) $id, $result[$id], $cacheGroup);
            }
        }

        if ($switched) {
            restore_current_blog();
        }

        $blockSets = [];
        foreach ($result as $id => $blocks) {
            $blockSets[$id] = new BlockSet(
                $this,
                $blockQuery->getOwnerId($id),
                $blocks
            );
        }

        return $blockSets;
    }

    private function blockSetForSite(BlockQueryForSite $blockQuery): array
    {
        $cacheGroup = self::CACHE_GROUP_PREFIX . $blockQuery->column();
        $siteId = $blockQuery->siteId();

        $blocks = $cached = wp_cache_get((string) $siteId, $cacheGroup);
        if ($blocks === false) {
            $blocks = $this->db->fetchBlocks($blockQuery);
            wp_cache_set((string) $siteId, $blocks, $cacheGroup);
        }

        return [$siteId => new BlockSet(
            $this,
            $blockQuery->getOwnerId($siteId),
            $blocks
        )];
    }

    public function save(BlockSet ...$blockSets): void
    {
        if (!$blockSets) {
            return;
        }

        $siteId = $blockSets[0]->ownerId()->siteId();

        $allBlocks = [];
        foreach ($blockSets as $blockSet) {
            if ($blockSet->ownerId()->siteId() !== $siteId) {
                throw new SiteMismatchException();
            }

            $allBlocks = array_merge($allBlocks, $blockSet->all());
        }

        $this->db->saveBlocks($siteId, $allBlocks);
    }
}
