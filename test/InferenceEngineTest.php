<?php

namespace agentecho\test;

use \agentecho\component\InferenceEngine;
use \agentecho\component\SemanticStructureParser;
use \agentecho\component\RuleBase;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class InferenceEngineTest extends \PHPUnit_Framework_TestCase
{
	public function testListAlternatives()
	{
		$Engine = new InferenceEngine();

		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("son(?x, ?y)");

		$knowledgeSources = array(new TestKnowledgeSource());

		$bindings = $Engine->bind($Query, $knowledgeSources, array());

		$this->assertEquals(array(
			array('x' => 'sjoerd', 'y' => 'kees'),
			array('x' => 'jan', 'y' => 'piet'),
			array('x' => 'piet', 'y' => 'otto'),
			array('x' => 'peter', 'y' => 'sjoerd'),
		), $bindings);
	}

	public function testGrandson()
	{
		$Engine = new InferenceEngine();

		$Parser = new SemanticStructureParser();
		$Query = $Parser->parse("grandson(?x, ?y)");

		$knowledgeSources = array(new TestKnowledgeSource());
		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase.echo'));

		$bindings = $Engine->bind($Query, $knowledgeSources, $ruleSources);

		$this->assertEquals(array(
			array('x' => 'jan', 'y' => 'otto'),
			array('x' => 'peter', 'y' => 'kees'),
		), $bindings);
	}
}
