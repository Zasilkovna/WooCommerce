<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use PacketeryTracy\Debugger;

// For security reasons, PacketeryTracy is visible only on localhost.
// You may force PacketeryTracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>PacketeryTracy: bar dump demo</h1>

<p>You can dump variables to bar in rightmost bottom egde.</p>

<?php
$arr = [10, 20.2, true, null, 'hello', (object) null, []];

bdump(get_defined_vars());

bdump($arr, 'The Array');

bdump('<a href="#">test</a>', 'String');


if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, PacketeryTracy is visible only on localhost. Look into the source code to see how to enable PacketeryTracy.</b></p>';
}
