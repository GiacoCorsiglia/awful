<?php
namespace Awful\Exceptions;

use Exception;

/**
 * Exception thrown when trying to call an unimplemented function or method.
 */
class UnimplementedException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Exception $previous = null)
    {
        $func = debug_backtrace()[0]['function'];
        parent::__construct("$func is unimplemented.", $code, $previous);
    }
}
