<?php

/**
 * MAIN PAGE of the Echo Web App.
 *
 * Its starts up the request processor and feeds it the current request parameters.
 */

use agentecho\web\Processor;

require_once __DIR__ . '/Processor.php';

$Processor = new Processor();
$Processor->run($_REQUEST);
