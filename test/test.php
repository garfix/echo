<?php

// start autoloading based on namespaces
require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\test\LabeledDAGTest;
use \agentecho\test\ParserTest;
use \agentecho\test\DBPediaTest;
use \agentecho\test\ProductionTest;

function r($string, $return = false)
{
	$trace = debug_backtrace();
	echo $trace[0]['file'] . ' (' . $trace[0]['line'] . '):' . "\n";
	return print_r($string, $return);
}

$Test = new LabeledDAGTest();
$Test->execute();

$Test = new ParserTest();
$Test->execute();

$Test = new DBPediaTest();
$Test->execute();

$Test = new ProductionTest();
$Test->execute();

