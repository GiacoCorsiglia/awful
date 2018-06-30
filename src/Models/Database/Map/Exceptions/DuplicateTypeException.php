<?php
namespace Awful\Models\Database\Map\Exceptions;

use Exception;

class DuplicateTypeException extends Exception
{
    public function __construct(string $type = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Duplicate type in map: '$type'.", $code, $previous);
    }
}
