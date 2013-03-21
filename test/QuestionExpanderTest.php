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
		$questions = $this->execute('father(?x, ?y) and brother(?y, ?z)');

		$this->assertEquals(2, count($questions));
		$this->assertSame('father(?x, ?y) and brother(?y, ?z)', (string)$questions[0]);
		$this->assertSame('parent(?x, ?y) and male(?x) and brother(?y, ?z)', (string)$questions[1]);
	}

	public function testExpansionOnExpansion()
	{
		$questions = $this->execute('home_2_school(?p)');

		$this->assertEquals(5, count($questions));
		$this->assertSame('home_2_school(?p)', (string)$questions[0]);
		$this->assertSame('home_2_town(?p, ?s1) and town_2_school(?s1, ?p)', (string)$questions[1]);
		$this->assertSame('home_2_main_street(?p, ?s2) and main_street_2_town(?s2, ?s1) and town_2_school(?s1, ?p)', (string)$questions[2]);
		$this->assertSame('home_2_main_street(?p, ?s2) and main_street_2_town(?s2, ?s1) and town_2_forest(?s1, ?s3) and forest_2_school(?s3, ?p)', (string)$questions[3]);
		$this->assertSame('home_2_town(?p, ?s1) and town_2_forest(?s1, ?s4) and forest_2_school(?s4, ?p)', (string)$questions[4]);
	}

//	public function testCompoundGoalClause()
//	{
//		$questions = $this->execute('yellow(?p) and red(?q) and green(?q) and blue(?p, ?q)');
//
//		$this->assertEquals(4, count($questions));
//		$this->assertSame('yellow(?p) and red(?q) and green(?q) and blue(?p, ?q)', (string)$questions[0]);
//		$this->assertSame('pear(?p, ?q) and red(?q) and blue(?p, ?q)', (string)$questions[1]);
//		$this->assertSame('yellow(?p) and red(?q) and banana(?q, ?p) and monkey(?q)', (string)$questions[2]);
//		$this->assertSame('pear(?p, ?q) and red(?q) and banana(?q, ?p) and monkey(?q)', (string)$questions[3]);
//
//		// it's just not a goal clause, it's a data mapper
//		// the only difference is that the parts that are not rewritten are just added as is,
//		// and that multiple passes may be applied
//		// but what is has in common with this goal applier is that multiple questions may result
//		// and please give meaningful examples! if you cannot give one, why create this at all?
//
//		yellow(?a) and green(?b) :- pear(?a, ?b)
//		green(?a) and blue(?b) :- banana(?b, ?a) and monkey(?b)
//
//	}
//
//	public function testAggregateClause()
//	{
//		# todo
//	}

	private function execute($sentence)
	{
		$Parser = new SemanticStructureParser();
		$Expander = new QuestionExpander();

		$ruleSources = array(new RuleBase(__DIR__ . '/../resources/testRuleBase3.echo'));
		$Structure = $Parser->parse($sentence);
		$questions = $Expander->findExpandedQuestions($Structure, $ruleSources);

		return $questions;
	}
}
