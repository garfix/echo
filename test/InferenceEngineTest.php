<?php

namespace agentecho\test;

use \agentecho\component\InferenceEngine;
use \agentecho\component\SemanticStructureParser;
use \agentecho\component\RuleBase;
use agentecho\test\helpers\TestKnowledgeSource1;
use agentecho\test\helpers\TestKnowledgeSource2;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class InferenceEngineTest extends \PHPUnit_Framework_TestCase
{
	public function testQueryKnowledgeSource_shouldListAlternatives()
	{
		$Engine = new InferenceEngine();

		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("son(?x, ?y)");

		$knowledgeSources = array(new TestKnowledgeSource1());

		$bindings = $Engine->bind($Query, $knowledgeSources, array());

		$this->assertEquals(array(
			array('x' => 'sjoerd', 'y' => 'kees'),
			array('x' => 'jan', 'y' => 'piet'),
			array('x' => 'piet', 'y' => 'otto'),
			array('x' => 'peter', 'y' => 'sjoerd'),
		), $bindings);
	}

	public function testSingleRuleWithTwoKnowledgeSourceLookups()
	{
		$Engine = new InferenceEngine();
		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("grandson(?x, ?y)");

		$knowledgeSources = array(new TestKnowledgeSource1());
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase1.echo'));

		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);

		$this->assertEquals(array(
			array('x' => 'jan', 'y' => 'otto'),
			array('x' => 'peter', 'y' => 'kees'),
		), $bindings);
	}

	/**
	 * This test needs the agent to use data from two different knowledge sources.
	 */
	public function testSingleRuleWithLookupsInDistinctKnowledgeSources()
	{
		$Engine = new InferenceEngine();

		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("brother('arie', 'kobus')");
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase1.echo'));

		// knowledge source 1 alone gives no answers
		$knowledgeSources = array(new TestKnowledgeSource1());
		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(), $bindings);

		// knowledge source 2 alone gives no answers
		$knowledgeSources = array(new TestKnowledgeSource2());
		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(), $bindings);

		// both knowledge sources together give the answer
		$knowledgeSources = array(new TestKnowledgeSource1(), new TestKnowledgeSource2());
		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(array()), $bindings);
	}

	/**
	 * Regression for incorrect separation of goal clause variable namespaces.
	 */
	public function testVariableNamespaceMixup()
	{
		$Engine = new InferenceEngine();
		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("abc(?x, ?y)");
		$knowledgeSources = array(new TestKnowledgeSource2());
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase1.echo'));

		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(array('x' => 'letter', 'y' => 'word')), $bindings);
	}

	public function testMultipleRulesFromMultipleRuleSources()
	{
		$Engine = new InferenceEngine();
		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("relative('johanna', ?y)");
		$knowledgeSources = array(new TestKnowledgeSource1());

		// rule source 1 alone gives no answers
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase1.echo'));
		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(), $bindings);

		// rule source 2 alone gives no answers
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase2.echo'));
		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(), $bindings);

		// the two sources combined give two answers
		$ruleSources = array(
			new RuleBase(__DIR__ . '/../resources/testRuleBase1.echo'),
			new RuleBase(__DIR__ . '/../resources/testRuleBase2.echo'));
		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);
		$this->assertEquals(array(
			// marie is johanna's mother
			array('y' => 'marie'),
			// roberta is johanna's aunt
			array('y' => 'roberta'),
		), $bindings);
	}

	/**
	 * Regression test. The constant 'bob' was not used as a constraint.
	 */
	public function testOneConstantAndOneVariable()
	{
		$Engine = new InferenceEngine();
		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("child('bob', ?y)");

		$knowledgeSources = array(new TestKnowledgeSource1());
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase1.echo'));

		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);

		$this->assertEquals(array(), $bindings);
	}

	public function testInfiniteRecursionProtection()
	{
		$caught = true;

		try {

			$Engine = new InferenceEngine();
			$Parser = new SemanticStructureParser();
			$Query = $Parser->parse("recurse(?a, ?b)");

			$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase2.echo'));

			$Engine->bind($Query, array(), $ruleSources);

		} catch(\agentecho\exception\RecursionException $Exception) {
			$caught = true;
		}

		$this->assertTrue($caught);
	}
}
