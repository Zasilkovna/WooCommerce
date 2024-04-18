<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette;

/**
 * The exception that is thrown when the value of an argument is
 * outside the allowable range of values as defined by the invoked method.
 * @internal
 */
class ArgumentOutOfRangeException extends \InvalidArgumentException
{
}
/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 * @internal
 */
class InvalidStateException extends \RuntimeException
{
}
/**
 * The exception that is thrown when a requested method or operation is not implemented.
 * @internal
 */
class NotImplementedException extends \LogicException
{
}
/**
 * The exception that is thrown when an invoked method is not supported. For scenarios where
 * it is sometimes possible to perform the requested operation, see InvalidStateException.
 * @internal
 */
class NotSupportedException extends \LogicException
{
}
/**
 * The exception that is thrown when a requested method or operation is deprecated.
 * @internal
 */
class DeprecatedException extends NotSupportedException
{
}
/**
 * The exception that is thrown when accessing a class member (property or method) fails.
 * @internal
 */
class MemberAccessException extends \Error
{
}
/**
 * The exception that is thrown when an I/O error occurs.
 * @internal
 */
class IOException extends \RuntimeException
{
}
/**
 * The exception that is thrown when accessing a file that does not exist on disk.
 * @internal
 */
class FileNotFoundException extends IOException
{
}
/**
 * The exception that is thrown when part of a file or directory cannot be found.
 * @internal
 */
class DirectoryNotFoundException extends IOException
{
}
/**
 * The exception that is thrown when an argument does not match with the expected value.
 * @internal
 */
class InvalidArgumentException extends \InvalidArgumentException
{
}
/**
 * The exception that is thrown when an illegal index was requested.
 * @internal
 */
class OutOfRangeException extends \OutOfRangeException
{
}
/**
 * The exception that is thrown when a value (typically returned by function) does not match with the expected value.
 * @internal
 */
class UnexpectedValueException extends \UnexpectedValueException
{
}
