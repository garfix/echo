<?php

namespace agentecho\test;

use agentecho\component\SemanticStructureParser;
use agentecho\exception\SemanticStructureParseException;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class SemanticStructureParserTest extends \PHPUnit_Framework_TestCase
{
	public function testPredicationWithConstant()
	{
		$Parser = new SemanticStructureParser();

		$string = 'name(a, "John")';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testPredicationWithAtom()
	{
		$Parser = new SemanticStructureParser();

		$string = 'isa(a, Bird)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testProperty()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP.sem';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

//	public function testPropertyProperty()
//	{
//		$Parser = new SemanticStructureParser();
//
//		$string = 'NP.first.sem';
//		$Structure = $Parser->parse($string);
//		$serialized = $Parser->serialize($Structure);
//		$this->assertEquals($string, $serialized);
//	}

	public function testParsePredicationList()
	{
		$Parser = new SemanticStructureParser();

		$string = 'name(a, "John") and name(b, "Mary") and love(a, b)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testParsePredicationWithPredicationList()
	{
		$Parser = new SemanticStructureParser();

		$string = 'question(q, first(q, e) and second(e, "answer"))';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testTokenizerFail()
	{
		$Parser = new SemanticStructureParser();
		$pos = false;

		try {
			$string = 'name#';
			$Parser->parse($string);
		} catch (SemanticStructureParseException $E) {
			$pos = $E->pos;
		}

		$this->assertEquals(4, $pos);
	}

	public function testParseFail()
	{
		$Parser = new SemanticStructureParser();
		$pos = false;

		try {
			$string = 'name(a) and ()';
			$Parser->parse($string);
		} catch (SemanticStructureParseException $E) {
			$pos = $E->pos;
		}

		$this->assertEquals(12, $pos);
	}
}
