<?php
namespace Awful\Database;

use Awful\Database\Exception\DatabaseException;
use wpdb;

class Database
{
    /** @var wpdb */
    private $wpdb;

    private const OPTION = 'awful_database_version';

    private const VERSION = '1';

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function install(int $siteId = 0): void
    {
        assert(!$siteId || (is_multisite() && $siteId), 'Expected $siteId only on multisite install');

        if ($siteId) {
            switch_to_blog($siteId);
        }

        if (get_option(self::OPTION) !== self::VERSION) {
            update_option(self::OPTION, self::VERSION, false);
            $this->createTable(!$siteId || is_main_site());
        }

        if ($siteId) {
            restore_current_blog();
        }
    }

    public function uninstall(int $siteId = 0): void
    {
        assert(!$siteId || (is_multisite() && $siteId), 'Expected $siteId only on multisite install');

        if ($siteId) {
            switch_to_blog($siteId);
        }

        delete_option(self::OPTION);
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table()};");
        $this->errorToException($this->wpdb->last_error);

        if ($siteId) {
            restore_current_blog();
        }
    }

    public function table(): string
    {
        return "{$this->wpdb->prefix}awful_blocks";
    }

    public function blocksForSite(): array
    {
        return $this->fetchBlocks('for_site', [1]);
    }

    public function blocksForPosts(int ...$postIds): array
    {
        return $this->fetchBlocks('post_id', $postIds);
    }

    public function blocksForTerms(int ...$termIds): array
    {
        return $this->fetchBlocks('term_id', $termIds);
    }

    public function blocksForUsers(int ...$userIds): array
    {
        return $this->fetchBlocks('user_id', $userIds);
    }

    public function blocksForComments(int ...$commentIds): array
    {
        return $this->fetchBlocks('comment_id', $commentIds);
    }

    private function fetchBlocks(string $column, array $values): array
    {
        $vals = implode(',', $values);
        $results = $this->wpdb->get_results("SELECT *
            FROM {$this->table()}
            WHERE `$column` IN ({$vals})
        ;");
        $this->errorToException($this->wpdb->last_error);
        /** @var array */
        return $results;
    }

    private function createTable(bool $includeUsers): void
    {
        // TODO: block_created/block_modified timestamps?
        // TODO: INDEX block_type?

        $table = $this->table();

        $userId = $includeUsers
            ? '`user_id` BIGINT(20) UNSIGNED,'
            : '';

        $userReference = $includeUsers
            ? "FOREIGN KEY (`user_id`) REFERENCES `{$this->wpdb->users}` (`ID`) ON DELETE CASCADE,"
            : '';

        $sql = "CREATE TABLE `$table` (
            `block_uuid` CHAR(36) NOT NULL,
            `block_parent_uuid` CHAR(36) DEFAULT NULL,
            `for_site` BOOLEAN,
            `post_id` BIGINT(20) UNSIGNED,
            `term_id` BIGINT(20) UNSIGNED,
            $userId
            `comment_id` BIGINT(20) UNSIGNED,
            `block_type` VARCHAR(255) NOT NULL,
            `block_data` JSON NOT NULL CHECK (JSON_VALID(block_data)),
            PRIMARY KEY (`block_uuid`),
            FOREIGN KEY (`block_parent_uuid`) REFERENCES `$table` (`block_uuid`),
            FOREIGN KEY (`post_id`) REFERENCES `{$this->wpdb->posts}` (`ID`) ON DELETE CASCADE,
            FOREIGN KEY (`term_id`) REFERENCES `{$this->wpdb->terms}` (`term_id`) ON DELETE CASCADE,
            $userReference
            FOREIGN KEY (`term_id`) REFERENCES `{$this->wpdb->comments}` (`comment_ID`) ON DELETE CASCADE
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
