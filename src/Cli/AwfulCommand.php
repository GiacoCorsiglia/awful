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
        if (!is_multisite()) {
            $this->db->install();
            return;
        }
    }

    public function uninstall(): void
    {
        if (!is_multisite()) {
            $this->db->uninstall();
            return;
        }
    }

    public function sayHello(): void
    {
        echo "hello\n";
    }
}
