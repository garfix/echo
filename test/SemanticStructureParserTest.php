<?php

namespace agentecho\test;

use agentecho\component\parser\SemanticStructureParser;
use agentecho\component\parser\ParseRulesParser;
use agentecho\exception\SemanticStructureParseException;
use agentecho\component\parser\GenerationRulesParser;
use agentecho\component\parser\LexiconParser;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class SemanticStructureParserTest extends \PHPUnit_Framework_TestCase
{
	public function testRelationWithConstant()
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

	public function testRelationWithAtomAndVariable()
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

	public function testRelationWithProperty()
	{
		$Parser = new SemanticStructureParser();

		$string = 'isa(noun.entity, Bird)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testRelationWithoutArguments()
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

	public function testParseRelationList()
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

	public function testAssignmentWithRelationList()
	{
		$Parser = new SemanticStructureParser();

		$string = 'NP.sem = subject(?event, ?subject)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testAssignmentWithPropertyAndRelation()
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

		$string = '{S.sem = NP.sem and VP.sem; S.event = VP.event; S.subject = NP.entity}';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
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

		$string = 'PN.sem = name(PN.subject, propernoun1.text + " " + propernoun2.text)';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testParseRule()
	{
		$Parser = new SemanticStructureParser();
		$string = '[rule: S => NP VP]';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testParseRules()
	{
		$Parser = new ParseRulesParser();

		$string = '[rule: S => NP VP]';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		// empty string
		$string = '';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testGenerationRules()
	{
		$Parser = new GenerationRulesParser();

		$string = '[rule: S => NP VP] [rule: S => VP]';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		// empty string
		$string = '';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testLexicon()
	{
		$Parser = new LexiconParser();

		$string = "[form: 'werd', partOfSpeech: 'auxPsv']";
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		// empty string
		$string = '';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testProductionRule()
	{
		$Parser = new SemanticStructureParser();
		$string = 'S => NP VP';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		$string = 'S => VP NP1 NP2';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);

		$string = 'premodifier =>';
		$Structure = $Parser->parse($string);
		$serialized = $Parser->serialize($Structure);
		$this->assertEquals($string, $serialized);
	}

	public function testTokenizerFail()
	{
		$Parser = new SemanticStructureParser();
		$caught = false;

		try {
			$string = 'name$';
			$Parser->parse($string);
		} catch (SemanticStructureParseException $E) {
			$caught = true;
		}

		$this->assertTrue($caught);
	}

	public function testParseFail()
	{
		$Parser = new SemanticStructureParser();
		$caught = false;

		try {
			$string = 'name(?a) and ()';
			$Parser->parse($string);
		} catch (SemanticStructureParseException $E) {
			$caught = true;
		}

		$this->assertTrue($caught);
	}
}
