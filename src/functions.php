<?php
/**
 * Utility functions for Awful.
 */
namespace Awful;

/**
 * Checks each element in `$array` against a `$predicate`; useful for verifying
 * arrays in assertions.
 *
 * @param array    $array     The array to check.
 * @param callable $predicate A function which returns a bool and accepts at
 *                            least one or two arguments, depending on `$flag`.
 *                            You can leave off the Awful namespace.
 * @param array    $args      Additional positional arguments to pass to the
 *                            $predicate after they value, key, or both,
 *                            depending on the $flag.
 * @param int      $flag      One of `ARRAY_FILTER_USE_KEY` or
 *                            `ARRAY_FILTER_USE_BOTH`. Defaults to 0, meaning
 *                            only the value will be passed to the `$predicate`.
 *
 * @return bool If each key-value pair in the array passes the predicate.
 */
function every(
    array $array,
    $predicate,
    array $args = [],
    int $flag = 0
): bool {
    if (is_string($predicate) && !is_callable($predicate)) {
        $predicate = __NAMESPACE__ . "\\$predicate";
    }
    assert(is_callable($predicate), 'Expected callable `$predicate`');

    foreach ($array as $key => $value) {
        switch ($flag) {
            case ARRAY_FILTER_USE_BOTH:
                $is = $predicate($value, $key, ...$args);
                break;
            case ARRAY_FILTER_USE_KEY:
                $is = $predicate($key, ...$args);
                break;
            default:
                $is = $predicate($value, ...$args);
        }
        if (!$is) {
            return false;
        }
    }
    return true;
}

/**
 * Determines if `$class` is a subclass of, or is identically, `$parent_class`.
 *
 * Use instead of `is_subclass_of()` if `$class` might equal `$parent_class`.
 *
 * @param mixed  $class        Expected to be a string, but confirms.
 * @param string $parent_class
 *
 * @return bool
 */
function is_subclass($class, string $parent_class): bool
{
    assert(class_exists($parent_class), 'Expected valid `$parent_class`');
    return $class === $parent_class || (is_string($class) && is_subclass_of($class, $parent_class));
}

/**
 * Determines if `$class` represents an implementation of `$interface`.
 *
 * Doesn't check if `$class` can be instantiated (e.g. if it's not abstract).
 *
 * @param mixed  $class     Expected to be a string, but confirms.
 * @param string $interface
 *
 * @return bool
 */
function is_implementation($class, string $interface): bool
{
    assert(interface_exists($interface), 'Expected valid `$interface`');
    return is_string($class) && !empty(class_implements($class)[$interface]);
}

/**
 * Determines if `$object` is of the `$class_or_interface` type.
 *
 * Just a functional wrapper around the `instanceof` operator.
 *
 * @param mixed  $object             Expected to be an object, but confirms.
 * @param string $class_or_interface
 *
 * @return bool
 */
function is_instanceof($object, string $class_or_interface): bool
{
    assert(class_exists($class_or_interface) || interface_exists($class_or_interface), 'Expected valid `$class_or_interface`');
    return is_object($object) && $object instanceof $class_or_interface;
}

/**
 * Tells whether an array is purely associative by checking if all its keys are
 * strings.
 *
 * @param array $array The array to check
 *
 * @return bool Whether or not the array is associative
 */
function is_associative(array $array): bool
{
    return every($array, 'is_string', [], ARRAY_FILTER_USE_KEY);
}

/**
 * Generate a random-enough v4 UUID for Awful's purposes.
 *
 * Be wary of using this function if you have strict randomness requirements.
 *
 * @see https://stackoverflow.com/a/15875555
 *
 * @return string A new UUID.
 */
function uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
