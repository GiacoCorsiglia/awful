<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Query\BlockQuery;
use stdClass;
use wpdb;

/**
 * A non-persistent, in-memory Database implementation for testing.
 */
class DatabaseMock extends Database
{
    /** @var array */
    private $blocksBySite = [];

    public function __construct(wpdb $wpdb = null)
    {
    }

    public function deleteBlocksFor(BlockQuery $blockQuery, array $uuids): void
    {
        $siteId = $blockQuery->siteId();
        if (empty($this->blocksBySite[$siteId])) {
            return;
        }
        $this->blocksBySite[$siteId] = array_filter(
            $this->blocksBySite[$siteId],
            function (stdClass $block) use ($blockQuery, $uuids): bool {
                $matchesQuery = in_array($block->{$blockQuery->column()}, $blockQuery->values());
                $matchesUuid = in_array($block->{self::UUID_COLUMN}, $uuids);
                return !($matchesQuery && $matchesUuid);
            }
        );
    }

    public function fetchBlocks(BlockQuery $blockQuery): array
    {
        $siteBlocks = $this->blocksBySite[$blockQuery->siteId()] ?? [];

        $return = [];
        $column = $blockQuery->column();
        $values = $blockQuery->values();
        foreach ($siteBlocks as $block) {
            if (in_array($block->{$column} ?? null, $values)) {
                $return[] = $block;
            }
        }
        return $return;
    }

    public function getBlocksForSite(int $siteId): array
    {
        return $this->blocksBySite[$siteId] ?? [];
    }

    public function install(int $siteId = 0): void
    {
    }

    public function saveBlocks(int $siteId, array $blocks): void
    {
        if (!isset($this->blocksBySite[$siteId])) {
            $this->blocksBySite[$siteId] = [];
        }

        $idColumn = self::ID_COLUMN;
        $dataColumn = self::DATA_COLUMN;

        foreach ($blocks as $block) {
            if ($existingBlock = $this->findById($siteId, $block->id ?? 0)) {
                // TBH this has probably already happened.
                $existingBlock->{$dataColumn} = $block->{$dataColumn};
            } else {
                $this->blocksBySite[$siteId][] = $this->cloneWithDefaults($block);
            }
        }
    }

    public function setBlocksForSite(int $siteId, array $blocks): void
    {
        $this->blocksBySite[$siteId] = array_map(/** @psalm-suppress MissingClosureParamType */ function ($block): stdClass {
            return $this->cloneWithDefaults($block);
        }, $blocks);
    }

    public function uninstall(int $siteId = 0): void
    {
    }

    /**
     * @param stdClass|array $block
     * @return stdClass
     */
    private function cloneWithDefaults($block): stdClass
    {
        $clone = (array) $block;
        return (object) ($clone + [
            self::ID_COLUMN => rand(),
            self::UUID_COLUMN => '',
            self::SITE_COLUMN => 0,
            self::USER_COLUMN => null,
            self::POST_COLUMN => null,
            self::TERM_COLUMN => null,
            self::COMMENT_COLUMN => null,
            self::TYPE_COLUMN => '',
            self::DATA_COLUMN => [],
        ]);
    }

    private function findById(int $siteId, int $id): ?stdClass
    {
        $idColumn = self::ID_COLUMN;

        foreach ($this->blocksBySite[$siteId] as $block) {
            if ($block->{$idColumn} === $id) {
                return $block;
            }
        }

        return null;
    }
}
