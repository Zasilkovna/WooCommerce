<?php

if ($argc < 4) {
    echo "Usage: php generate-graph.php <token> <branchName> <outputFile>\n";
    exit(1);
}

// Configuration
$owner = 'Zasilkovna';
$repo = 'WooCommerce';
$startDate = '2025-02-19';

$token = $argv[1];
$branchName = $argv[2];
$outputFile = $argv[3];

require_once __DIR__ . '/PacketeryGraphicCodeCoverage.php';

header( 'Content-Type: text/plain' );
PacketeryGraphicCodeCoverage::downloadDataToCsv($owner, $repo, $token, $startDate);
PacketeryGraphicCodeCoverage::generateImage($branchName, $outputFile);
