<?php

namespace Packetery;

// Don't redefine the functions if included multiple times.
if (!\function_exists('Packetery\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
