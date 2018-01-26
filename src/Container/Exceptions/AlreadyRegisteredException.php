<?php
namespace Awful\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class AlreadyRegisteredException extends Exception implements ContainerExceptionInterface
{
}
