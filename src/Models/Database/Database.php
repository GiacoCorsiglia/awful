<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Exceptions\DatabaseException;
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

    public function tableForSite(int $siteId): string
    {
        $switched = $siteId && get_current_blog_id() !== $siteId;
        if ($switched) {
            switch_to_blog($siteId);
        }

        $table = $this->table();

        if ($switched) {
            restore_current_blog();
        }

        return $table;
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

        $dataColumn = self::DATA_COLUMN;
        // Decode the JSON.
        foreach ($blocks as $block) {
            // Pass `true` so `$data` becomes an associative array.
            $block->$dataColumn = $block->$dataColumn ? (json_decode($block->$dataColumn, true) ?: []) : [];
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

        $idColumn = self::ID_COLUMN;
        $uuidColumn = self::UUID_COLUMN;
        $siteColumn = self::SITE_COLUMN;
        $userColumn = self::USER_COLUMN;
        $postColumn = self::POST_COLUMN;
        $termColumn = self::TERM_COLUMN;
        $commentColumn = self::COMMENT_COLUMN;
        $typeColumn = self::TYPE_COLUMN;
        $dataColumn = self::DATA_COLUMN;

        $includeUsers = !$siteId || is_main_site();
        $userColumnDeclaration = $includeUsers ? "`$userColumn`," : '';

        $sql = "INSERT into {$this->table()} (
            `$idColumn`,
            `$uuidColumn`,
            `$siteColumn`,
            $userColumnDeclaration
            `$postColumn`,
            `$termColumn`,
            `$commentColumn`,
            `$typeColumn`,
            `$dataColumn`
        ) VALUES ";

        $rows = [];
        $values = []; // The values that will be passed to wpdb::prepare()
        foreach ($blocks as $block) {
            // wpdb::prepare() doesn't support NULL, so we have to sanitize the
            // foreign key columns ourself.  We do so by just casting to `int`.

            $row = [];
            $row[] = (int) ($block->$idColumn ?? 0);
            $row[] = '%s'; // uuid
            $row[] = ($block->$siteColumn ?? 0) ? 1 : 0;
            if ($includeUsers) {
                $row[] = ((int) ($block->$userColumn ?? 0)) ?: 'NULL';
            }
            $row[] = ((int) ($block->$postColumn ?? 0)) ?: 'NULL';
            $row[] = ((int) ($block->$termColumn ?? 0)) ?: 'NULL';
            $row[] = ((int) ($block->$commentColumn ?? 0)) ?: 'NULL';
            $row[] = '%s'; // type
            $row[] = '%s'; // data

            $rows[] = '(' . implode(',', $row) . ')';

            $values[] = $block->$uuidColumn;
            $values[] = $block->$typeColumn;
            $values[] = !empty($block->$dataColumn) ? json_encode($block->$dataColumn) : '{}';
        }

        $sql .= implode(',', $rows);
        $sql .= " ON DUPLICATE KEY UPDATE `$dataColumn` = VALUES(`$dataColumn`);";

        /**
         * @psalm-suppress PossiblyNullArgument
         * $wpdb->prepare will always return a string in this case.
         */
        $this->wpdb->query($this->wpdb->prepare($sql, $values));
        $this->errorToException($this->wpdb->last_error);

        if ($switched) {
            restore_current_blog();
        }
    }

    private function table(): string
    {
        return "{$this->wpdb->prefix}awful_blocks";
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
            INDEX (`$siteColumn`),
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
