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

			link(Of , ?s , ?sub) and
			name(?sub, 'Lord Byron')
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

	public function testAnswerWithCalculation()
	{
		$question = '
			manner ( ?S_complement , ?S_request ) and
			isa ( ?S_complement , Old ) and
			tense ( ?S_event , Past ) and
			name ( ?S_subject , "Mary Shelley" ) and
			subject ( ?S_event , ?S_subject ) and
			modifier ( ?S_event , ?S_complement ) and
			request ( ?S_request ) and
			mood ( ?S_event , Interrogative ) and
			at_time ( ?S_event , ?S_Clause_SBar_subEvent ) and
			isa ( ?S_subject , Female ) and
			reference ( ?S_subject ) and
			isa ( ?S_Clause_SBar_subEvent , Die ) and
			subject ( ?S_Clause_SBar_subEvent , ?S_subject ) and
			object ( ?S_Clause_SBar_subEvent , ?S_Clause_SBar_Clause_object ) and
			mood ( ?S_Clause_SBar_subEvent , Declarative ) and
			sentence ( ?S_event )
		';

		$answerBindings = array(
			array(
				'S_subject' => 'http://dbpedia.org/resource/Mary_Shelley',
                's3' => '1851-02-01',
                's1' => 'http://dbpedia.org/resource/Mary_Shelley',
                's2' => '1797-08-30',
                'S_request' => 53,
			)
		);
#todo: there is a superfluous mood(?S_event, Interrogative)
		$answer = 'manner(?S_complement, ?S_request) and isa(?S_complement, Old) and tense(?S_event, Past) and name(?S_subject, "Mary Shelley") and '.
			'subject(?S_event, ?S_subject) and modifier(?S_event, ?S_complement) and request(?S_request) and mood(?S_event, Interrogative) and at_time(?S_event, ?S_Clause_SBar_subEvent) and ' .
			'isa(?S_subject, Female) and reference(?S_subject) and isa(?S_Clause_SBar_subEvent, Die) and subject(?S_Clause_SBar_subEvent, ?S_subject) and ' .
			'object(?S_Clause_SBar_subEvent, ?S_Clause_SBar_Clause_object) and mood(?S_Clause_SBar_subEvent, Declarative) and sentence(?S_event) and ' .
			'mood(?S_event, Declarative) and modifier(?S_complement, ?s1) and determiner(?s1, "53") and isa(?s1, Year)';

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
