<?php

namespace agentecho\test;

use agentecho\component\QuestionExpander;
use agentecho\component\SemanticStructureParser;
use agentecho\component\RuleBase;

require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class QuestionExpanderTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleExpansion()
	{
		$Parser = new SemanticStructureParser();
		$Expander = new QuestionExpander();

		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase3.echo'));
		$Structure = $Parser->parse('father(?x, ?y) and brother(?y, ?z)');

		$questions = $Expander->findExpandedQuestions($Structure, $ruleSources);

		$this->assertEquals(2, count($questions));
		$this->assertSame('father(?x, ?y) and brother(?y, ?z)', (string)$questions[0]);
		$this->assertSame('parent(?x, ?y) and male(?x) and brother(?y, ?z)', (string)$questions[1]);
	}

	public function testExpansionOnExpansion()
	{
		$Parser = new SemanticStructureParser();
		$Expander = new QuestionExpander();

		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase3.echo'));
		$Structure = $Parser->parse('home_2_school(?p)');

		$questions = $Expander->findExpandedQuestions($Structure, $ruleSources);

		$this->assertEquals(5, count($questions));
		$this->assertSame('home_2_school(?p)', (string)$questions[0]);
		$this->assertSame('home_2_town(?p, ?s1) and town_2_school(?s1, ?p)', (string)$questions[1]);
		$this->assertSame('home_2_main_street(?p, ?s2) and main_street_2_town(?s2, ?s1) and town_2_school(?s1, ?p)', (string)$questions[2]);
		$this->assertSame('home_2_main_street(?p, ?s2) and main_street_2_town(?s2, ?s1) and town_2_forest(?s1, ?s3) and forest_2_school(?s3, ?p)', (string)$questions[3]);
		$this->assertSame('home_2_town(?p, ?s1) and town_2_forest(?s1, ?s4) and forest_2_school(?s4, ?p)', (string)$questions[4]);
	}
}
