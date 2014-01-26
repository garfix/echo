<?php

namespace agentecho\test;

use agentecho\component\Answerer;
use agentecho\component\Formulator;
use agentecho\component\GrammarFactory;
use agentecho\component\KnowledgeManager;
use agentecho\component\parser\SemanticStructureParser;
use agentecho\datastructure\Constant;
use agentecho\datastructure\RelationList;
use agentecho\knowledge\KnowledgeSource;
use agentecho\test\helpers\AnswerTestKnowledgeSource;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class FormulatorTest extends \PHPUnit_Framework_TestCase
{
	public function testAnswerWhereQuestion()
	{
		$question = "
			sentence(?e) and
			mood(?e, Interrogative) and
			tense(?e, Past) and
			aspect(?e, Perfect) and
			isa(?e, Bear) and

			subject(?e, ?s) and
			name(?s, 'Lord Byron') and

			location(?e, ?request) and
			request(?request)
		";

		$answer = 'sentence(?e) and mood(?e, Interrogative) and tense(?e, Past) and aspect(?e, Perfect) and isa(?e, Bear) and subject(?e, ?s) and name(?s, "Lord Byron") and ' .
			'location(?e, ?request) and request(?request) and link(In, ?e, ?p) and name(?p, "London")';


		$answerBindings = array(
			array(
				'request' => 'London',
			)
		);

		$this->doTest($question, $answer, $answerBindings);
	}

	public function testAnswerWithConjunction()
	{
		// the question is irrelevant; it will be ignored
		$question = "
			sentence (?e) and
			tense(?e , Past) and
			mood(?e, Interrogative) and

			subject (?e, ?s) and
			isa (?s , Child) and
			identity(?s, ?request) and
			request (?request) and

			object (?e, ?o) and

			link(Of , ?s , ?S_Clause_NP_DP_subEntity) and
			name(?S_Clause_NP_DP_subEntity , 'Lord Byron')
		";

		$answer = 'sentence(?n) and name(?n1, "Allegra Byron") and name(?n2, "Ada Lovelace") and link(And, ?n, ?n2, ?n1)';

		$answerBindings = array(
			array(
				'request' => 'Ada Lovelace',
			),
			array(
				'request' => 'Allegra Byron',
			),
		);

		$this->doTest($question, $answer, $answerBindings);
	}

	private function doTest($question, $answer, array $answerBindings)
	{
		$Parser = new SemanticStructureParser();
		$Formulator = new Formulator(__DIR__ . '/helpers/AnswerTestAnswerMap.formulations');

		/** @var RelationList $Sentence */
		$Sentence = $Parser->parse($question);

		$Answer = $Formulator->formulate($Sentence, $answerBindings);
		$this->assertSame($answer, (string)$Answer);

	}
}
