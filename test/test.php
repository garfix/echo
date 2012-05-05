<?php

namespace agentecho\test;

// start autoloading based on namespaces
require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\test\LabeledDagTest;
use \agentecho\test\LanguageTest;

function test($case, $got, $expected) {
	if ($expected !== $got) {
		echo 'Test failed: ' . $case . "\n";
		echo "Expected: " . print_r($expected, true) . "\n";
		echo "got:      " . print_r($got, true) . "\n\n";
	}
}


function r($string, $return = false)
{
	return print_r($string, $return);
}
$Test = new LabeledDagTest();
$Test->test();

$Test = new LanguageTest();
$Test->test();

