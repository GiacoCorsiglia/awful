<?php
namespace Awful\Models\Database;

use Awful\AwfulTestCase;
use Awful\Models\Database\Query\BlockQueryForPosts;
use Awful\Models\Database\Query\BlockQueryForSite;
use function Awful\uuid;

class DatabaseTest extends AwfulTestCase
{
    /** @var Database */
    private $db;

    /** @var \wpdb */
    private $wpdb;

    public function setUp()
    {
        parent::setUp();

        $this->wpdb = $GLOBALS['wpdb'];

        // Allow tests here to create real tables.  We will manually clean up.
        remove_filter('query', [$this, '_create_temporary_tables']);
        remove_filter('query', [$this, '_drop_temporary_tables']);
        // Furthermore allow queries to really go through.
        $this->wpdb->query('COMMIT;'); // Start new transaction.
        $this->wpdb->query('SET AUTOCOMMIT = 1;');

        $this->db = new Database($this->wpdb);
    }

    public function tearDown()
    {
        // Ensure all the tables are deleted even if the tests fail.
        $tables = $this->wpdb->get_col("SHOW TABLES LIKE '%\\_awful\\_blocks'");
        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE `$table`");
        }

        parent::tearDown();
    }

    public function testColumnNames()
    {
        // Hard code this invariant because changing these names would break all
        // existing installs.
        $columns = [
            Database::ID_COLUMN => 'id',
            Database::UUID_COLUMN => 'uuid',
            Database::SITE_COLUMN => 'for_site',
            Database::USER_COLUMN => 'user_id',
            Database::POST_COLUMN => 'post_id',
            Database::TERM_COLUMN => 'term_id',
            Database::COMMENT_COLUMN => 'comment_id',
            Database::TYPE_COLUMN => 'type',
            Database::DATA_COLUMN => 'data',
        ];
        foreach ($columns as $actual => $expected) {
            $this->assertSame($expected, $actual);
        }
    }

    public function testDeleteBlocksFor()
    {
        $siteId = is_multisite() ? 1 : 0;

        $this->db->install($siteId);

        if ($siteId) {
            switch_to_blog($siteId);
        }
        $postId2 = $this->factory->post->create_and_get()->ID;
        $postId1 = $this->factory->post->create_and_get()->ID;
        if ($siteId) {
            restore_current_blog();
        }

        $table = $this->db->tableForSite($siteId);

        $idColumn = Database::ID_COLUMN;
        $uuidColumn = Database::UUID_COLUMN;
        $siteColumn = Database::SITE_COLUMN;
        $postColumn = Database::POST_COLUMN;
        $typeColumn = Database::TYPE_COLUMN;
        $dataColumn = Database::DATA_COLUMN;

        $this->wpdb->query("INSERT INTO `$table` (
            `$uuidColumn`,
            `$siteColumn`,
            `$postColumn`,
            `$typeColumn`,
            `$dataColumn`
        ) VALUES
            ('uuid1', 1, NULL, 'type1', '{}'),
            ('uuid2', 1, NULL, 'type2', '{}'),
            ('uuid3', 1, NULL, 'type2', '{}'),
            ('uuid1', 0, $postId1, 'type1', '{}'),
            ('uuid2', 0, $postId1, 'type2', '{}'),
            ('uuid4', 0, $postId1, 'type2', '{}')
        ;");

        $selectCount = function (string $where) use ($table) {
            return $this->wpdb->get_var("SELECT COUNT(*) FROM `$table` WHERE $where");
        };

        $siteQuery = new BlockQueryForSite($siteId);
        $postQuery = new BlockQueryForPosts($siteId, $postId1);

        // Sanity check beforehand.
        $this->assertSame('3', $selectCount($siteQuery->sql()));
        $this->assertSame('3', $selectCount($postQuery->sql()));

        // Run the deletion.
        $this->db->deleteBlocksFor($siteQuery, ['uuid1', 'uuid3']);

        // Ensure it worked.
        $this->assertSame('1', $selectCount($siteQuery->sql()), 'Correct number of site blocks remain.');
        $this->assertSame('0', $selectCount("{$siteQuery->sql()} AND `$uuidColumn` IN ('uuid1', 'uuid3')"), 'Correct site blocks were deleted.');

        $this->assertSame('3', $selectCount($postQuery->sql()), 'It does not delete the post blocks.');

        $this->db->uninstall($siteId);
    }

    public function testFetchBlocks()
    {
        $siteId = is_multisite() ? 1 : 0;

        $this->db->install($siteId);

        if ($siteId) {
            switch_to_blog($siteId);
        }
        $postId2 = $this->factory->post->create_and_get()->ID;
        $postId1 = $this->factory->post->create_and_get()->ID;
        if ($siteId) {
            restore_current_blog();
        }

        $table = $this->db->tableForSite($siteId);

        $idColumn = Database::ID_COLUMN;
        $uuidColumn = Database::UUID_COLUMN;
        $siteColumn = Database::SITE_COLUMN;
        $postColumn = Database::POST_COLUMN;
        $typeColumn = Database::TYPE_COLUMN;
        $dataColumn = Database::DATA_COLUMN;

        $this->wpdb->query("INSERT INTO `$table` (
            `$uuidColumn`,
            `$siteColumn`,
            `$postColumn`,
            `$typeColumn`,
            `$dataColumn`
        ) VALUES
            ('a', 1, NULL, 'type1', '{ \"hello\": \"hi\" }'),
            ('aa', 1, NULL, 'type2', '{}'),
            ('b', 0, $postId1, 'type1', '{ \"hello\": \"hi\" }'),
            ('bb', 0, $postId1, 'type2', '{}'),
            ('b', 0, $postId2, 'type1', '{ \"hello\": \"hi\" }'),
            ('bb', 0, $postId2, 'type2', '{}')
        ;");

        $siteQuery = new BlockQueryForSite($siteId);
        $siteBlocks = $this->db->fetchBlocks($siteQuery);
        $this->assertSame(2, count($siteBlocks), 'Correct number of blocks found');
        // The value is returned as a string.
        $this->assertEquals(1, $siteBlocks[0]->{$siteColumn}, 'For site is correct');
        $this->assertEquals(1, $siteBlocks[1]->{$siteColumn}, 'For site is correct');

        $this->assertEquals(['hello' => 'hi'], $siteBlocks[0]->{$dataColumn}, 'Data is decoded correctly.');

        $allPostQuery = new BlockQueryForPosts($siteId, $postId1, $postId2);
        $allPostBlocks = $this->db->fetchBlocks($allPostQuery);
        $this->assertSame(4, count($allPostBlocks), 'Correct number of blocks found');

        $onePostQuery = new BlockQueryForPosts($siteId, $postId1);
        $onePostBlocks = $this->db->fetchBlocks($onePostQuery);
        $this->assertSame(2, count($onePostBlocks), 'Correct number of blocks found');
        // The value is returned as a string.
        $this->assertEquals($postId1, $onePostBlocks[0]->{$postColumn}, 'Post ID matches (1)');
        $this->assertEquals($postId1, $onePostBlocks[1]->{$postColumn}, 'Post ID matches (2)');

        $this->db->uninstall($siteId);
    }

    public function testInstallAndUninstall()
    {
        if (is_multisite()) {
            $userId = wpmu_create_user('foo', 'foo', 'foo@foo.com');
            $siteId = wpmu_create_blog('foo.com', '/', 'Test', $userId, ['public' => 1]);
        } else {
            $siteId = 0;
        }

        $table = $this->db->tableForSite($siteId);

        $this->assertTrue($this->wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table, 'The table has not yet been created.');
        $this->db->install($siteId);
        $this->db->install($siteId); // Should be idempotent.
        $this->assertTrue($this->wpdb->get_var("SHOW TABLES LIKE '$table'") === $table, 'The table has been created.');
        $this->db->uninstall($siteId);
        $this->db->uninstall($siteId); // Should be idempotent.
        $this->assertTrue($this->wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table, 'The table was deleted.');

        if (is_multisite()) {
            wpmu_delete_blog($siteId, true);
        }
    }

    public function testSaveBlocks()
    {
        if (is_multisite()) {
            $userId = wpmu_create_user('foo', 'foo', 'foo@foo.com');
            $siteId = wpmu_create_blog('foo.com', '/', 'Test', $userId, ['public' => 1]);
        } else {
            $siteId = 0;
        }

        $this->db->install($siteId);
        $table = $this->db->tableForSite($siteId);

        // User assertEquals since `get_var` will return a string.
        $this->assertEquals(0, $this->wpdb->get_var("SELECT COUNT(*) FROM `$table`;"));

        if ($siteId) {
            switch_to_blog($siteId);
        }
        $postId = $this->factory->post->create_and_get()->ID;
        if ($siteId) {
            restore_current_blog();
        }

        $postBlockUuid = uuid();
        $postBlockType = 'bar';
        $originalPostBlockData = ['b' => ['c', 'd']];

        $this->db->saveBlocks($siteId, [
            (object) [
                Database::UUID_COLUMN => uuid(),
                Database::SITE_COLUMN => 1,
                Database::TYPE_COLUMN => 'foo',
                Database::DATA_COLUMN => ['a' => 5],
            ],
            (object) [
                Database::UUID_COLUMN => $postBlockUuid,
                Database::POST_COLUMN => $postId,
                Database::TYPE_COLUMN => $postBlockType,
                Database::DATA_COLUMN => $originalPostBlockData,
            ],
        ]);

        $this->assertEquals(2, $this->wpdb->get_var("SELECT COUNT(*) FROM `$table`;"), '2 blocks were inserted');
        $this->assertEquals(1, $this->wpdb->get_var("SELECT COUNT(*) FROM `$table` WHERE `" . Database::SITE_COLUMN . '` = 1;'), 'One for_site block was inserted');
        $this->assertEquals(1, $this->wpdb->get_var("SELECT COUNT(*) FROM `$table` WHERE `" . Database::POST_COLUMN . "` = $postId;"), 'One post_id block was inserted');

        $id = $this->wpdb->get_var("SELECT `id` FROM `$table` WHERE `" . Database::POST_COLUMN . "` = $postId;");

        $this->assertSame($postBlockType, $this->wpdb->get_var('SELECT `' . Database::TYPE_COLUMN . "` FROM `$table` WHERE `" . Database::ID_COLUMN . "` = $id;"));

        $this->assertSame($originalPostBlockData, json_decode($this->wpdb->get_var('SELECT `' . Database::DATA_COLUMN . "` FROM `$table` WHERE `" . Database::ID_COLUMN . "` = $id;"), true));

        // Save some more blocks; this time one of them should update.
        $updatedPostBlockData = ['foo' => 'bar'];
        $this->db->saveBlocks($siteId, [
            (object) [
                Database::UUID_COLUMN => uuid(),
                Database::SITE_COLUMN => 1,
                Database::TYPE_COLUMN => 'hi',
                Database::DATA_COLUMN => [],
            ],
            (object) [
                Database::ID_COLUMN => $id,
                Database::UUID_COLUMN => $postBlockUuid,
                Database::TYPE_COLUMN => $postBlockType,
                Database::DATA_COLUMN => $updatedPostBlockData,
            ],
        ]);

        $this->assertEquals(3, $this->wpdb->get_var("SELECT COUNT(*) FROM `$table`;"), '1 new block was inserted');

        $this->assertSame($postBlockUuid, $this->wpdb->get_var('SELECT `' . Database::UUID_COLUMN . "` FROM `$table` WHERE `" . Database::ID_COLUMN . "` = $id;"));

        $this->assertSame($updatedPostBlockData, json_decode($this->wpdb->get_var('SELECT `' . Database::DATA_COLUMN . "` FROM `$table` WHERE `" . Database::ID_COLUMN . "` = $id;"), true));

        $this->db->uninstall($siteId);

        if (is_multisite()) {
            wpmu_delete_blog($siteId, true);
        }
    }

    public function testTableForSite()
    {
        if (is_multisite()) {
            switch_to_blog(1);
            $this->assertSame("{$this->wpdb->prefix}awful_blocks", $this->db->tableForSite(1));
            restore_current_blog();

            $userId = wpmu_create_user('foo', 'foo', 'foo@foo.com');
            $siteId = wpmu_create_blog('foo.com', '/', 'Test', $userId, ['public' => 1]);

            switch_to_blog($siteId);
            $prefix = $this->wpdb->prefix;
            restore_current_blog();

            switch_to_blog(1);
            $this->assertSame("{$prefix}awful_blocks", $this->db->tableForSite($siteId));
            restore_current_blog();

            wpmu_delete_blog($siteId, true);
        } else {
            $this->assertSame("{$this->wpdb->prefix}awful_blocks", $this->db->tableForSite(0));
        }
    }
}
