<?php

// start autoloading based on namespaces
require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\test\LabeledDAGTest;
use \agentecho\test\ParseTest;
use \agentecho\test\DBPediaTest;

function r($string, $return = false)
{
	return print_r($string, $return);
}

$Test = new LabeledDAGTest();
$Test->execute();

$Test = new ParseTest();
$Test->execute();

$Test = new DBPediaTest();
$Test->execute();

