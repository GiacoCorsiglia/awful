<?php
/**
 * Utility functions for Awful.
 */
namespace Awful;

/**
 * Checks each element in `$array` against a `$predicate`; useful for verifying
 * arrays in assertions.
 *
 * @param array           $array     The array to check.
 * @param callable|string $predicate Either a callable which returns a boolean,
 *                                   or the string 'instanceof'.
 * @param array           $args      Additional positional arguments to pass to
 *                                   the $predicate after they value, key, or
 *                                   both, depending on the $flag.
 * @param int             $flag      One of `ARRAY_FILTER_USE_KEY` or
 *                                   `ARRAY_FILTER_USE_BOTH`. Defaults to 0,
 *                                   meaning only the value will be passed to
 *                                   the `$predicate`.
 *
 * @return bool If each key-value pair in the array passes the predicate.
 */
function every(
    array $array,
    $predicate,
    array $args = [],
    int $flag = 0
): bool {
    assert($predicate === 'instanceof' || is_callable($predicate), 'Expected callable');

    if ($predicate === 'instanceof') {
        assert(!empty($args[0]) && class_exists($args[0]));
        assert($flag === 0);
        $predicate = function ($value, $class) {
            return $value instanceof $class;
        };
    }

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
 * Determines if `$class is a subclass of, or is identically, `$parent_class`.
 *
 * Use instead of `is_subclass_of()` if `$class` might equal `$parent_class`.
 *
 * @param string $class
 * @param string $parent_class
 *
 * @return bool
 */
function is_subclass(string $class, string $parent_class): bool
{
    return $class === $parent_class || is_subclass_of($class, $parent_class);
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
