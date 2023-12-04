<?php

namespace Packetery;

use Packetery\VCR\VCR;
require_once __DIR__ . '/../vendor/autoload.php';
\define('Packetery\\FIXTURE_DIR', \realpath(__DIR__ . '/fixtures'));
\define('Packetery\\VCR_CASSETTE_DIR', \realpath(__DIR__ . '/fixtures/vcr'));
\Packetery\VCR\VCR::configure()->setCassettePath(\Packetery\VCR_CASSETTE_DIR)->enableLibraryHooks(['soap', 'curl']);
VCR::turnOn();
