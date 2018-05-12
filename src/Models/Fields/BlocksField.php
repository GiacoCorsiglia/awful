<?php
namespace Awful\Models\Fields;

use Awful\Models\Block;
use Awful\Models\Database\Exceptions\UnknownBlockTypeException;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;
use function Awful\every;

class BlocksField extends Field
{
    public function __construct(array $args = [])
    {
        assert(every($args['types'], 'is_subclass_of', [Block::class]));

        parent::__construct($args);
    }

    public function toPhp($value, Model $model, string $fieldKey)
    {
        return new BlocksFieldInstance((array) $value, $model, $fieldKey, $this->args['types']);
    }

    public function clean($value, Model $model): array
    {
        if (!$value) {
            return [];
        }

        if (!is_array($value)) {
            throw new ValidationException('Expected an array of uuids.');
        }

        $blockSet = $model->blockSet();
        $blockTypeMap = $blockSet->blockTypeMap();
        foreach ($value as $uuid) {
            $record = $blockSet->get($uuid);
            if (!$record) {
                throw new ValidationException("Block '$uuid' does not exist.");
            }

            try {
                $class = $blockTypeMap->classForType($record->type);
            } catch (UnknownBlockTypeException $e) {
                throw new ValidationException("Unknown block type '{$record->type}'", 0, $e);
            }

            if (!in_array($class, $this->args['types'])) {
                throw new ValidationException("Disallowed block class '$class'");
            }
        }

        return $value;
    }
}
