<?php
namespace Awful\Models\Fields;

use Awful\Models\Block;
use Awful\Models\Database\Map\Exceptions\UnknownTypeException;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;
use function Awful\every;

class BlocksField extends Field
{
    protected const DEFAULTS = [
        'min' => 0,
        'max' => 0,
    ] + Field::DEFAULTS;

    public function __construct(array $args = [])
    {
        assert(!empty($args['types']) && every($args['types'], 'is_subclass_of', [Block::class]), "Expected non-empty array of Block subclasses for 'types'.");

        parent::__construct($args);

        assert(is_int($this->args['min']) && $this->args['min'] >= 0, "Expected positive integer for 'min'.");
        assert(is_int($this->args['max']) && $this->args['max'] >= 0, "Expected positive integer for 'max'.");
        assert($this->args['max'] >= $this->args['min'], "Expected 'max' >= 'min'.");
    }

    public function clean($value, Model $model): array
    {
        if (!$value) {
            return [];
        }

        if (!is_array($value)) {
            throw new ValidationException('Expected an array of uuids.');
        }

        $count = count($value);
        $min = $this->args['min'];
        if ($min && $count < $min) {
            throw new ValidationException("Requires at least $min entries.");
        }
        $max = $this->args['max'];
        if ($max && $count > $max) {
            throw new ValidationException("May include at most $max entries.");
        }

        $blockSet = $model->blockSet();
        $blockTypeMap = $blockSet->blockTypeMap();
        foreach ($value as $uuid) {
            $record = $blockSet->get($uuid);
            if (!$record) {
                throw new ValidationException("Block '$uuid' does not exist.");
            }

            if (empty($record->type)) {
                throw new ValidationException("Block '$uuid' does not specify a type.");
            }

            try {
                $class = $blockTypeMap->classForType($record->type);
            } catch (UnknownTypeException $e) {
                throw new ValidationException("Unknown block type '{$record->type}'", 0, $e);
            }

            if (!in_array($class, $this->args['types'])) {
                throw new ValidationException("Disallowed block class '$class'");
            }
        }

        return $value;
    }

    public function toPhp($value, Model $model, string $fieldKey)
    {
        return new BlocksFieldInstance((array) $value, $model, $fieldKey, $this->args['types']);
    }
}
