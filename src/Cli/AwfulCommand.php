<?php
namespace Awful\Cli;

use Awful\Models\Database\Database;

class AwfulCommand extends Command
{
    const COMMAND_NAME = 'awful';

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function install(): void
    {
        foreach ($this->allSiteIds() as $siteId) {
            $this->db->install($siteId);
        }
    }

    public function uninstall(): void
    {
        foreach ($this->allSiteIds() as $siteId) {
            $this->db->uninstall($siteId);
        }
    }

    /**
     * @return int[]
     * @psalm-return array<int, int>
     */
    private function allSiteIds(): array
    {
        if (!is_multisite()) {
            return [0];
        }
        /** @psalm-var array<int,int> */
        return get_sites([
            'fields' => 'ids',
            'number' => 0,
        ]);
    }
}
