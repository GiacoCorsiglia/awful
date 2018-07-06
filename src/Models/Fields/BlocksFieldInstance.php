<?php
namespace Awful\Models\Fields;

use Awful\Models\Block;
use Awful\Models\Database\BlockSet;
use Awful\Models\Database\Map\Exceptions\UnknownTypeException;
use Awful\Models\Model;
use Awful\Models\WordPressModel;

class BlocksFieldInstance extends ObjectsFieldInstance
{
    /** @var BlockSet */
    private $blockSet;

    /** @var string */
    private $fieldKey;

    /** @var Model */
    private $model;

    public function __construct(
        array $uuids,
        Model $model,
        string $fieldKey,
        array $allowedClasses
    ) {
        $this->fieldKey = $fieldKey;
        $this->model = $model;

        $this->blockSet = $model->blockSet();

        /** @var WordPressModel */
        $owner = null;
        if ($model instanceof Block) {
            $owner = $model->owner();
        } elseif ($model instanceof WordPressModel) {
            $owner = $model;
        } else {
            throw new \Exception('Unknown model type.');
        }

        $blockTypeMap = $this->blockSet->blockTypeMap();
        foreach ($uuids as $uuid) {
            $record = $this->blockSet->get($uuid);

            if (!$record) {
                continue;
            }

            try {
                $class = $blockTypeMap->classForType($record->type);
            } catch (UnknownTypeException $e) {
                continue;
            }

            if (!in_array($class, $allowedClasses)) {
                continue;
            }

            $this->ids[] = $uuid;
            $this->objects[] = new $class($owner, $uuid);
        }
    }

    protected function emit(): void
    {
        $this->model->set([$this->fieldKey => $this->ids]);
    }

    protected function validateAndGetId(object $object): string
    {
        if (!$object instanceof Block) {
            throw new \Exception();
        }
        if ($object->blockSet() !== $this->blockSet) {
            throw new \Exception();
        }
        return $object->uuid();
    }
}
