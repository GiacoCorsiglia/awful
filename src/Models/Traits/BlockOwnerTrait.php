<?php
namespace Awful\Models\Traits;

use Awful\Models\BlockSet;

trait BlockOwnerTrait
{
    /**
     * @var BlockSet
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $blockSet;

    public function blockSet(): BlockSet
    {
        return $this->blockSet;
    }

    abstract protected function rootBlockType(): string;

    protected function initializeBlockSet(BlockSet $blockSet): void
    {
        assert($this->blockSet === null, 'Cannot initialize blockSet more than once');
        $this->blockSet = $blockSet;

        if ($root = $this->blockSet->firstOfType($this->rootBlockType())) {
            $this->initializeData(json_decode($root->data));
        } else {
            $this->initializeData([]);
        }
    }

    abstract protected function initializeData(array $data): void;
}
