<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use PacketeryTracy\Debugger;

// session is required for this functionality
session_start();

// For security reasons, PacketeryTracy is visible only on localhost.
// You may force PacketeryTracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');


if (isset($_GET['sleep'])) {
	header('Content-Type: application/javascript');
	sleep(10);
	exit;
}

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>PacketeryTracy: Preloading</h1>

<?php Debugger::renderLoader() ?>

<script src="?sleep=1"></script>


<?php

if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, PacketeryTracy is visible only on localhost. Look into the source code to see how to enable PacketeryTracy.</b></p>';
}
