<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Schema;

/** @internal */
interface Schema
{
    /**
     * Normalization.
     * @return mixed
     */
    function normalize($value, Context $context);
    /**
     * Merging.
     * @return mixed
     */
    function merge($value, $base);
    /**
     * Validation and finalization.
     * @return mixed
     */
    function complete($value, Context $context);
    /**
     * @return mixed
     */
    function completeDefault(Context $context);
}
