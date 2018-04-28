<?php
namespace Awful\Models\Database;

/**
 * Responsible for creating/dropping the blocks table on site addition/deletion.
 */
class MultisiteDatabaseHooks
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        add_action('wpmu_new_blog', [$this, 'createSite']);
        add_filter('wpmu_drop_tables', [$this, 'addTableToDropOnDeleteSite']);
    }

    /**
     * @param  int  $blog_id ID of the site
     * @return void
     */
    public function createSite($blog_id): void
    {
        $this->db->install((int) $blog_id);
    }

    /**
     * @param  string[] $tables
     * @param  int      $blog_id
     * @return array
     */
    public function addTableToDropOnDeleteSite($tables, $blog_id): array
    {
        $tables[] = $this->db->tableForSite((int) $blog_id);
        return $tables;
    }
}
