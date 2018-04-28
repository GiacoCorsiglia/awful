<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Exceptions\UnknownBlockTypeException;
use Awful\Models\Database\Exceptions\UnregisteredBlockClassException;

class BlockTypeMap
{
    /**
     * @var array
     * @psalm-var array<class-string, string>
     */
    private $classToTypeMap = [];

    /**
     * @var array
     * @psalm-var array<string, class-string>
     */
    private $typeToClassMap = [];

    /**
     * @param array $classToTypesMap
     * @psalm-param array<class-string, string[]|string> $classToTypesMap
     */
    public function __construct(array $classToTypesMap)
    {
        foreach ($classToTypesMap as $class => $types) {
            $types = (array) $types;

            $this->classToTypeMap[$class] = $types[0];

            foreach ($types as $type) {
                $this->typeToClassMap[$type] = $class;
            }
        }
    }

    /**
     * @param  string $type
     * @return string
     * @psalm-return class-string
     */
    public function classForType(string $type): string
    {
        if (empty($this->typeToClassMap[$type])) {
            throw new UnknownBlockTypeException($type);
        }
        return $this->typeToClassMap[$type];
    }

    /**
     * @param string $class
     * @psalm-param class-string
     * @return string
     */
    public function typeForClass(string $class): string
    {
        if (empty($this->classToTypeMap[$class])) {
            throw new UnregisteredBlockClassException($class);
        }
        return $this->classToTypeMap[$class];
    }
}
