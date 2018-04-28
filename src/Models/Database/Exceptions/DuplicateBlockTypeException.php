<?php
namespace Awful\Models\Database\Exceptions;

use Exception;

class DuplicateBlockTypeException extends Exception
{
    public function __construct(string $type = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Duplicate block type: '$type'.", $code, $previous);
    }
}
