<?php
namespace Awful\Models\Database;

class EntityManager
{
    /** @var BlockSetManager */
    private $blockSetManager;

    public function __construct(BlockSetManager $blockSetManager)
    {
        $this->blockSetManager = $blockSetManager;
    }

    public function blockSetManager(): BlockSetManager
    {
        return $this->blockSetManager;
    }
}
