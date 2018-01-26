<?php
namespace Awful\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class ClassDoesNotExistException extends Exception implements ContainerExceptionInterface
{
}
