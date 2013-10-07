<?php

namespace agentecho\test;

use agentecho\component\Utils;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class UtilsTest extends \PHPUnit_Framework_TestCase
{
	public function testCreatePermutations()
	{
		$input = array(
			array('a', 'c'),
			array('x', 'z'),
			array('2', '4')
		);

		$output = Utils::createPermutations($input);

		$expected = array(
			array('a', 'x', '2'),
			array('a', 'x', '4'),
			array('a', 'z', '2'),
			array('a', 'z', '4'),
			array('c', 'x', '2'),
			array('c', 'x', '4'),
			array('c', 'z', '2'),
			array('c', 'z', '4'),
		);

		$this->assertEquals($expected, $output);
	}

	public function testCreatePermutationsWithEmptyRow()
	{
		$input = array(
			array('a', 'c'),
			array(),
			array('2', '4')
		);

		$output = Utils::createPermutations($input);

		$expected = array();

		$this->assertEquals($expected, $output);
	}

	public function testStringifyArray()
	{
		$this->assertEquals('a: 1, b: 2', Utils::stringify(array('a' => 1, 'b' => 2)));
		$this->assertEquals('a: 1, b: (c: 2, d: 3)', Utils::stringify(array('a' => 1, 'b' => array('c' => 2, 'd' => 3))));
	}
}
