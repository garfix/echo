<?php

use agentecho\web\Processor;

require_once __DIR__ . '/Processor.php';

$Processor = new Processor();
$Processor->run($_REQUEST);
