<?php
namespace Awful\Models\Database\Map;

use Awful\Models\Database\Map\Exceptions\DuplicateTypeException;
use Awful\Models\Database\Map\Exceptions\UnknownTypeException;
use Awful\Models\Database\Map\Exceptions\UnregisteredClassException;

abstract class AbstractTypeMap
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
     * @psalm-param array<class-string, array<int, string>|string> $classToTypesMap
     */
    public function __construct(array $classToTypesMap)
    {
        foreach ($classToTypesMap as $class => $types) {
            $types = (array) $types;

            $this->classToTypeMap[$class] = $types[0];

            foreach ($types as $type) {
                if (isset($this->typeToClassMap[$type])) {
                    throw new DuplicateTypeException($type);
                }
                $this->typeToClassMap[$type] = $class;
            }
        }
    }

    /**
     * @param string $type
     *
     * @return string
     * @psalm-return class-string
     */
    public function classForType(string $type): string
    {
        if (empty($this->typeToClassMap[$type])) {
            throw new UnknownTypeException($type);
        }
        return $this->typeToClassMap[$type];
    }

    /**
     * @param string $class
     * @psalm-param class-string $class
     *
     * @return string
     */
    public function typeForClass(string $class): string
    {
        if (empty($this->classToTypeMap[$class])) {
            throw new UnregisteredClassException($class);
        }
        return $this->classToTypeMap[$class];
    }
}
