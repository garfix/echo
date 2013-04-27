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

		$string = 'name(?a, "John")';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAllowTrailingWhitespace()
	{
		$Parser = new SemanticStructureParser();

		$string = 'name(?a, "John") ';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$string = 'name(?a, "John")';
		$this->assertEquals($string, $serialized);
	}

	public function testPredicationWithAtomAndVariable()
	{
		$Parser = new SemanticStructureParser();

		$string = 'isa(?a, Bird)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		$string = 'isa(?A, bird)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testPredicationWithProperty()
	{
		$Parser = new SemanticStructureParser();

		$string = 'isa(noun.object, Bird)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testPredicationWithoutArguments()
	{
		$Parser = new SemanticStructureParser();

		$string = 'true()';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAtom()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP';
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

	public function testPropertyProperty()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP.first.second.sem';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testParsePredicationList()
	{
		$Parser = new SemanticStructureParser();

		$string = 'name(?a, "John") and name(?b, "Mary") and love(?a, ?b)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAssignmentWithProperty()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP.sem = noun.sem';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAssignmentWithPredicationList()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP.sem = subject(?event, ?subject)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAssignmentWithPropertyAndPredication()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP.sem = noun.sem and subject(?event, ?subject)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAssignmentList()
	{
		$Parser = new SemanticStructureParser();

		$string = 'S.sem = NP.sem and VP.sem; S.event = VP.event; S.subject = NP.object';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testGoalClause()
	{
		$Parser = new SemanticStructureParser();

		$string = 'grandfather(?x, ?z) :- father(?x, ?y) and father(?y, ?z)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testMultiplePredicationGoal()
	{
		$Parser = new SemanticStructureParser();

		$string = 'wet(?x) and cold(?x) :- rains(?x)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testGoalClauseWithLet()
	{
		$Parser = new SemanticStructureParser();

		$string = 'grandfather(?x, ?z) :- father(?x, ?y) and let(?z, older(?y))';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		// regression: check if `older` was parsed as a function application
		$this->assertTrue($Structure->getMeans()->getPredication(1)->getArgument(1) instanceof \agentecho\datastructure\FunctionApplication);
	}

	public function testMap()
	{
		$Parser = new SemanticStructureParser();

		// single mapping with multiple consequents
		$string = 'age(?p, ?a) => born(?p, ?d1) and die(?p, ?d2) and diff(?d2, ?d1, ?a)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		// a series of datamappings
		$string = 'a(?x) => b(?x); c(?x) => d(?x); e(?x) => f(?x)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		// trailing semicolon
		$string = 'a(?x) => b(?x); c(?x) => d(?x);';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals('a(?x) => b(?x); c(?x) => d(?x)', $serialized);

		// comment
		$string = '
			a(?x) => b(?x);
			// comment
			c(?x) => d(?x)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals('a(?x) => b(?x); c(?x) => d(?x)', $serialized);
	}

	public function testAssignmentWithOperation()
	{
		$Parser = new SemanticStructureParser();

		$string = 'PN.name = propernoun1.text + " " + propernoun2.text';
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
			$string = 'name(?a) and ()';
			$Parser->parse($string);
		} catch (SemanticStructureParseException $E) {
			$pos = $E->pos;
		}

		$this->assertEquals(13, $pos);
	}
}
