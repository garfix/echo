<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\component\Generator;
use agentecho\component\GrammarFactory;
use agentecho\component\parser\SemanticStructureParser;
use agentecho\datastructure\PredicationList;

/**
 * @author Patrick van Bergen
 */
class GenerationTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleDeclarativeActiveSentence()
	{
		// note: tense is implicitly the present

		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			isa(?e, Walk) and
			subject(?e, ?s) and
			name(?s, 'John')
		";

		$this->doTest($relations, "en", "John walks.");
		$this->doTest($relations, "nl", "John loopt.");
	}

	public function testVerbWithExplicitPastTense()
	{
		// note: tense is explicitly the past

		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			isa(?e, Walk) and
			tense(?e, Past) and
			subject(?e, ?s) and
			name(?s, 'John')
		";

		$this->doTest($relations, "en", "John walked.");
		$this->doTest($relations, "nl", "John liep.");
	}

	public function testVerbWithDirectObject()
	{
		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			isa(?e, Influence) and
			tense(?e, Past) and
			subject(?e, ?s) and
			object(?e, ?o) and
			name(?s, 'John Milton') and
			name(?o, 'Lord Byron')
		";

		$this->doTest($relations, "en", "John Milton influenced Lord Byron.");
		$this->doTest($relations, "nl", "John Milton beïnvloedde Lord Byron.");
	}

	public function testSimplePassiveSentence()
	{
		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			voice(?e, Passive) and
			isa(?e, Influence) and
			tense(?e, Past) and
			subject(?e, ?s) and
			object(?e, ?o) and
			name(?s, 'John Milton') and
			name(?o, 'Lord Byron')
		";

		$this->doTest($relations, "en", "Lord Byron was influenced by John Milton.");
		$this->doTest($relations, "nl", "Lord Byron werd beïnvloed door John Milton.");
	}

	public function testAffirmativeSentence()
	{
		$relations = "
			sentence(?e, S.event) and
			isa(?e, Meet) and
			mood(?e, Declarative) and
			tense(?e, Past) and
			subject(?e, ?s) and
			object(?e, ?o) and
			name(?s, 'Harry') and
			name(?o, 'Sally') and

			qualification(?e, ?a) and
			isa(?a, Yes)
		";

		$this->doTest($relations, "en", "Yes, Harry met Sally.");
		$this->doTest($relations, "nl", "Ja, Harry ontmoette Sally.");
	}

	public function testPrepositionalPhrase()
	{
		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			voice(?e, Passive) and
			isa(?e, Influence) and
			tense(?e, Past) and
			subject(?e, ?s) and
			object(?e, ?o) and
			isa(?s, Author) and
			determiner(?s, The) and
			link (Of, ?s, ?p) and
			name(?o, 'Lord Byron') and
			name(?p, 'Paradise Lost')
		";

		$this->doTest($relations, "en", "Lord Byron was influenced by the author of Paradise Lost.");
		$this->doTest($relations, "nl", "Lord Byron werd beïnvloed door de auteur van Paradise Lost.");
	}

	public function testHaveAndNumericDeterminer()
	{
		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			isa(?e, Have) and
			tense(?e, Past) and
			subject(?e, ?s) and
			name(?s, 'Mary') and
			object(?e, ?o) and
			isa(?o, Child) and
			determiner(?s, 2)
		";

		$this->doTest($relations, "en", "Mary had 2 children.");
		$this->doTest($relations, "nl", "Mary had 2 kinderen.");
	}

	public function testCopularSentence()
	{
		$relations = "
			sentence(?e, S.event) and
			mood(?e, Declarative) and
			tense(?e, Past) and
			subject(?e, ?s) and
			name(?s, 'Ada Lovelace') and
			complement(?e, ?c) and
			isa(?c, Daughter) and
			determiner(?c, The) and
			link(Of, ?c, ?p) and
			name(?p, 'Lord Byron')
		";

		$this->doTest($relations, "en", "Ada Lovelace was the daughter of Lord Byron.");
		$this->doTest($relations, "nl", "Ada Lovelace was de dochter van Lord Byron.");
	}

	public function testSimpleConjunction()
	{
		$relations = "
			sentence(?n1, CP.node) and
			link(And, ?n1, ?n2, ?n3) and
			name(?n2, 'Ada Lovelace') and
			name(?n3, 'Allegra Byron')
		";

		$this->doTest($relations, "en", "Ada Lovelace and Allegra Byron.");
		$this->doTest($relations, "nl", "Ada Lovelace en Allegra Byron.");
	}

	public function testConjunction()
	{
		$relations = "
			sentence(?n1, CP.node) and
			link(And, ?n1, ?e1, ?n2) and
			link(And, ?n2, ?e2, ?n3) and
			link(And, ?n3, ?e3, ?e4) and
			name(?e1, 'Joe') and
			name(?e2, 'William') and
			name(?e3, 'Jack') and
			name(?e4, 'Averell')
		";

		$this->doTest($relations, "en", "Joe, William, Jack and Averell.");
		$this->doTest($relations, "nl", "Joe, William, Jack en Averell.");
	}

	/**
	 * @param $relations
	 * @param $language
	 * @param $expected
	 */
	public function doTest($relations, $language, $expected)
	{
		$Parser = new SemanticStructureParser();
		$Generator = new Generator();

		/** @var PredicationList $Sentence */
		$Sentence = $Parser->parse($relations);

		$surfaceRepresentation = $Generator->generate($Grammar = GrammarFactory::getGrammar($language), $Sentence);
		$this->assertSame($expected, $surfaceRepresentation);
	}
}
