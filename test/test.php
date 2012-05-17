<?php

// start autoloading based on namespaces
require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\test\LabeledDagTest;
use \agentecho\test\LanguageTest;

function r($string, $return = false)
{
	return print_r($string, $return);
}

$Test = new LabeledDagTest();
$Test->execute();

$Test = new LanguageTest();
$Test->execute();

