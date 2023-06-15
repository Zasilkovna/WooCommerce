<?php

declare (strict_types=1);
namespace Packetery;

require __DIR__ . '/../src/tracy.php';
\Packetery\Tracy\OutputDebugger::enable();
function head()
{
    echo '<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">';
}
head();
echo '<h1>Output Debugger demo</h1>';
