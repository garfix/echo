<?php

namespace agentecho\test;

class TestBase
{
	protected function test($case, $got, $expected)
	{
		if ($expected !== $got) {
			echo 'Test failed: ' . $case . "\n";
			echo "Expected: " . print_r($expected, true) . "\n";
			echo "got:      " . print_r($got, true) . "\n\n";
		}
	}
}