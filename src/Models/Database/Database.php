<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Exception\DatabaseException;
use Awful\Models\Database\Query\BlockQuery;
use wpdb;

class Database
{
    public const ID_COLUMN = 'id';

    public const UUID_COLUMN = 'uuid';

    public const SITE_COLUMN = 'for_site';

    public const USER_COLUMN = 'user_id';

    public const POST_COLUMN = 'post_id';

    public const TERM_COLUMN = 'term_id';

    public const COMMENT_COLUMN = 'comment_id';

    public const TYPE_COLUMN = 'type';

    public const DATA_COLUMN = 'data';

    public const FOREIGN_KEY_COLUMNS = [
        self::SITE_COLUMN,
        self::USER_COLUMN,
        self::POST_COLUMN,
        self::TERM_COLUMN,
        self::COMMENT_COLUMN,
    ];

    private const OPTION = 'awful_database_version';

    private const VERSION = '1';

    /** @var wpdb */
    private $wpdb;

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function install(int $siteId = 0): void
    {
        assert(!$siteId || (is_multisite() && $siteId), 'Expected $siteId only when multisite');

        $switched = $siteId && get_current_blog_id() !== $siteId;
        if ($switched) {
            switch_to_blog($siteId);
        }

        if (get_option(self::OPTION) !== self::VERSION) {
            update_option(self::OPTION, self::VERSION, false);
            $this->createTable(!$siteId || is_main_site());
        }

        if ($switched) {
            restore_current_blog();
        }
    }

    public function uninstall(int $siteId = 0): void
    {
        assert(!$siteId || (is_multisite() && $siteId), 'Expected $siteId only when multisite');

        $switched = $siteId && get_current_blog_id() !== $siteId;
        if ($switched) {
            switch_to_blog($siteId);
        }

        delete_option(self::OPTION);
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table()};");
        $this->errorToException($this->wpdb->last_error);

        if ($switched) {
            restore_current_blog();
        }
    }

    public function table(): string
    {
        return "{$this->wpdb->prefix}awful_blocks";
    }

    public function fetchBlocks(BlockQuery $blockQuery): array
    {
        $siteId = $blockQuery->siteId();

        $switched = $siteId && get_current_blog_id() !== $siteId;
        if ($switched) {
            switch_to_blog($siteId);
        }

        $sql = "SELECT * FROM {$this->table()} WHERE {$blockQuery->sql()}";
        /**
         * @var array
         * @psalm-var array<int, \stdClass>
         */
        $blocks = $this->wpdb->get_results($sql);
        $this->errorToException($this->wpdb->last_error);

        if ($switched) {
            restore_current_blog();
        }

        // Decode the JSON.
        foreach ($blocks as $block) {
            // Pass `true` so `$data` becomes an associative array.
            $block->data = $block->data ? (json_decode($block->data, true) ?: []) : [];
        }

        return $blocks;
    }

    public function saveBlocks(int $siteId, array $blocks): void
    {
        assert(!$siteId || (is_multisite() && $siteId), 'Expected $siteId only when multisite');

        if (!$blocks) {
            return;
        }

        $switched = $siteId && get_current_blog_id() !== $siteId;
        if ($switched) {
            switch_to_blog($siteId);
        }

        $includeUsers = !$siteId || is_main_site();
        $userColumn = $includeUsers ? '`user_id`,' : '';

        $sql = "INSERT into {$this->table()} (
            `block_uuid`,
            `block_parent_uuid`,
            `for_site`,
            `post_id`,
            `term_id`,
            $userColumn
            `comment_id`,
            `block_type`,
            `block_data`
        ) VALUES ";

        $rows = [];
        $userPlaceholder = $includeUsers ? '%d,' : '';
        foreach ($blocks as $block) {
            $values = [
                $block->block_uuid,
                $block->block_parent_uuid,
                $block->for_site,
                $block->post_id,
                $block->term_id,
            ];
            if ($includeUsers) {
                $values[] = $block->user_id;
            }
            $values[] = $block->comment_id;
            $values[] = $block->block_type;
            $values[] = json_encode($block->block_data ?? []);

            $rows[] = $this->wpdb->prepare(
                "(%s, %s, %d, %d, %d, $userPlaceholder %d, %s, %s)",
                $values
            );
        }

        $sql .= implode(',', $rows);
        $sql .= ' ON DUPLICATE KEY UPDATE block_data=VALUES(block_data)';

        $this->wpdb->query($sql);
        $this->errorToException($this->wpdb->last_error);

        if ($switched) {
            restore_current_blog();
        }
    }

    private function createTable(bool $includeUsers): void
    {
        // TODO: created/modified timestamps?
        // TODO: INDEX `type`?

        $table = $this->table();

        $idColumn = self::ID_COLUMN;
        $uuidColumn = self::UUID_COLUMN;
        $siteColumn = self::SITE_COLUMN;
        $userColumn = self::USER_COLUMN;
        $postColumn = self::POST_COLUMN;
        $termColumn = self::TERM_COLUMN;
        $commentColumn = self::COMMENT_COLUMN;
        $typeColumn = self::TYPE_COLUMN;
        $dataColumn = self::DATA_COLUMN;

        // We'll only include the user column declaration on the main site.
        $userColumnDeclaration = $includeUsers
            ? "`$userColumn` BIGINT(20) UNSIGNED,"
            : '';
        $userReferenceDeclaration = $includeUsers
            ? "FOREIGN KEY (`$userColumn`) REFERENCES `{$this->wpdb->users}` (`ID`) ON DELETE CASCADE,"
            : '';

        $sql = "CREATE TABLE `$table` (
            `$idColumn` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `$uuidColumn` CHAR(36) NOT NULL,
            `$siteColumn` BOOLEAN,
            $userColumnDeclaration
            `$postColumn` BIGINT(20) UNSIGNED,
            `$termColumn` BIGINT(20) UNSIGNED,
            `$commentColumn` BIGINT(20) UNSIGNED,
            `$typeColumn` VARCHAR(255) NOT NULL,
            `$dataColumn` JSON NOT NULL CHECK (JSON_VALID(`$dataColumn`)),
            PRIMARY KEY (`$idColumn`),
            $userReferenceDeclaration
            FOREIGN KEY (`$postColumn`) REFERENCES `{$this->wpdb->posts}` (`ID`) ON DELETE CASCADE,
            FOREIGN KEY (`$termColumn`) REFERENCES `{$this->wpdb->terms}` (`term_id`) ON DELETE CASCADE,
            FOREIGN KEY (`$commentColumn`) REFERENCES `{$this->wpdb->comments}` (`comment_ID`) ON DELETE CASCADE
        ) {$this->wpdb->get_charset_collate()};";

        $this->wpdb->query($sql);

        $this->errorToException($this->wpdb->last_error);
    }

    private function errorToException(string $error): void
    {
        if ($error) {
            throw new DatabaseException($error);
        }
    }
}
