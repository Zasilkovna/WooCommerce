<?php

namespace Packetery\GuzzleHttp\Promise;

/**
 * Interface used with classes that return a promise.
 * @internal
 */
interface PromisorInterface
{
    /**
     * Returns a promise.
     *
     * @return PromiseInterface
     */
    public function promise();
}
