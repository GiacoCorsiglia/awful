<?php
namespace Awful\Models\Database\Exceptions;

use Exception;

class BlockNotFoundException extends Exception
{
    public function __construct(string $uuid = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("No block found for UUID: '$uuid'.", $code, $previous);
    }
}
