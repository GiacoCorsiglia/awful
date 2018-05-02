<?php
namespace Awful\Models\Database\Exceptions;

use Exception;

class UuidCollisionException extends Exception
{
    public function __construct(string $uuid = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Duplicate UUID: '$uuid'.", $code, $previous);
    }
}
