#!/usr/bin/php
<?php

require_once __DIR__ . '/bot/tests/LabeledDagTest.php';
require_once __DIR__ . '/bot/tests/LanguageTest.php';

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

testLabeledDAG();
testLanguage();
