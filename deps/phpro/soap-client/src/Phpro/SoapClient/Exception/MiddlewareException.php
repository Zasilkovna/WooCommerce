<?php

namespace Packetery\Phpro\SoapClient\Exception;

use Packetery\Http\Client\Exception;
/** @internal */
class MiddlewareException extends RuntimeException
{
    public static function fromHttPlugException(Exception $exception) : self
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception);
    }
}
