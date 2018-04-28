<?php
namespace Awful\Models\Database\Exceptions;

use Exception;

class UnknownBlockTypeException extends Exception
{
    public function __construct(string $type = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Unknown block type: '$type'.", $code, $previous);
    }
}
