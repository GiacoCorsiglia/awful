<?php
namespace Awful\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class UninstantiatableClassException extends Exception implements ContainerExceptionInterface
{
}
