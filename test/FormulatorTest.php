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
			sentence(?e)
			mood(?e, Interrogative)
			tense(?e, Past)
			aspect(?e, Perfect)
			isa(?e, Bear)

			subject(?e, ?s)
			name(?s, 'Lord Byron')

			location(?e, ?request)
			request(?request)
		";

		$answer = 'sentence(?e) mood(?e, Interrogative) tense(?e, Past) aspect(?e, Perfect) isa(?e, Bear) subject(?e, ?s) name(?s, "Lord Byron") ' .
			'location(?e, ?request) request(?request) link(In, ?e, ?p) name(?p, "London")';

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
			sentence (?e)
			tense(?e , Past)
			mood(?e, Interrogative)

			subject (?e, ?s)
			isa (?s , Child)
			identity(?s, ?request)
			request (?request)

			object (?e, ?o)

			link(Of , ?s , ?sub)
			name(?sub, 'Lord Byron')
		";

		$answer = 'sentence(?n) name(?n5, "Allegra Byron") name(?n6, "Ada Lovelace") link(And, ?n, ?n6, ?n5)';

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
			manner ( ?S_complement , ?S_request )
			isa ( ?S_complement , Old )
			tense ( ?S_event , Past )
			name ( ?S_subject , "Mary Shelley" )
			subject ( ?S_event , ?S_subject )
			modifier ( ?S_event , ?S_complement )
			request ( ?S_request )
			mood ( ?S_event , Interrogative )
			at_time ( ?S_event , ?S_Clause_SBar_subEvent )
			isa ( ?S_subject , Female )
			reference ( ?S_subject )
			isa ( ?S_Clause_SBar_subEvent , Die )
			subject ( ?S_Clause_SBar_subEvent , ?S_subject )
			object ( ?S_Clause_SBar_subEvent , ?S_Clause_SBar_Clause_object )
			mood ( ?S_Clause_SBar_subEvent , Declarative )
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
		$answer = 'manner(?S_complement, ?S_request) isa(?S_complement, Old) tense(?S_event, Past) name(?S_subject, "Mary Shelley") '.
			'subject(?S_event, ?S_subject) modifier(?S_event, ?S_complement) request(?S_request) mood(?S_event, Interrogative) at_time(?S_event, ?S_Clause_SBar_subEvent) ' .
			'isa(?S_subject, Female) reference(?S_subject) isa(?S_Clause_SBar_subEvent, Die) subject(?S_Clause_SBar_subEvent, ?S_subject) ' .
			'object(?S_Clause_SBar_subEvent, ?S_Clause_SBar_Clause_object) mood(?S_Clause_SBar_subEvent, Declarative) sentence(?S_event) ' .
			'mood(?S_event, Declarative) modifier(?S_complement, ?s1) determiner(?s1, 53) isa(?s1, Year)';

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
