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

		$this->doTest($question, $answer);
	}

	private function doTest($question, $answer)
	{
		$Parser = new SemanticStructureParser();
		$Formulator = new Formulator(__DIR__ . '/helpers/AnswerTestAnswerMap.formulations');

		$answerBindings = array(
			array(
				'request' => 'London',
			)
		);

		/** @var RelationList $Sentence */
		$Sentence = $Parser->parse($question);

		$Answer = $Formulator->formulate($Sentence, $answerBindings);
		$this->assertSame($answer, (string)$Answer);

	}
}
