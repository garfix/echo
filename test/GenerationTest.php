<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\component\Generator;
use agentecho\component\GrammarFactory;
use agentecho\component\parser\SemanticStructureParser;
use agentecho\datastructure\RelationList;

/**
 * @author Patrick van Bergen
 */
class GenerationTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleDeclarativeActiveSentence()
	{
		// note: tense is implicitly the present

		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			isa(?e, Walk)
			subject(?e, ?s)
			name(?s, 'John')
		";

		$this->doTest($relations, "en", "John walks.");
		$this->doTest($relations, "nl", "John loopt.");
	}

	public function testVerbWithExplicitPastTense()
	{
		// note: tense is explicitly the past

		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			isa(?e, Walk)
			tense(?e, Past)
			subject(?e, ?s)
			name(?s, 'John')
		";

		$this->doTest($relations, "en", "John walked.");
		$this->doTest($relations, "nl", "John liep.");
	}

	public function testVerbWithDirectObject()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			isa(?e, Influence)
			tense(?e, Past)
			subject(?e, ?s)
			object(?e, ?o)
			name(?s, 'John Milton')
			name(?o, 'Lord Byron')
		";

		$this->doTest($relations, "en", "John Milton influenced Lord Byron.");
		$this->doTest($relations, "nl", "John Milton beïnvloedde Lord Byron.");
	}

	public function testSimplePassiveSentence()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			voice(?e, Passive)
			isa(?e, Influence)
			tense(?e, Past)
			subject(?e, ?s)
			object(?e, ?o)
			name(?s, 'John Milton')
			name(?o, 'Lord Byron')
		";

		$this->doTest($relations, "en", "Lord Byron was influenced by John Milton.");
		$this->doTest($relations, "nl", "Lord Byron werd beïnvloed door John Milton.");
	}

	public function testAffirmativeSentence()
	{
		$relations = "
			sentence(?e)
			isa(?e, Meet)
			mood(?e, Declarative)
			tense(?e, Past)
			subject(?e, ?s)
			object(?e, ?o)
			name(?s, 'Harry')
			name(?o, 'Sally')

			qualification(?e, ?a)
			isa(?a, Yes)
		";

		$this->doTest($relations, "en", "Yes, Harry met Sally.");
		$this->doTest($relations, "nl", "Ja, Harry ontmoette Sally.");
	}

	public function testPrepositionalPhrase()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			voice(?e, Passive)
			isa(?e, Influence)
			tense(?e, Past)
			subject(?e, ?s)
			object(?e, ?o)
			isa(?s, Author)
			determiner(?s, The)
			link (Of, ?s, ?p)
			name(?o, 'Lord Byron')
			name(?p, 'Paradise Lost')
		";

		$this->doTest($relations, "en", "Lord Byron was influenced by the author of Paradise Lost.");
		$this->doTest($relations, "nl", "Lord Byron werd beïnvloed door de auteur van Paradise Lost.");
	}

	public function testHaveAndNumericDeterminer()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			isa(?e, Have)
			tense(?e, Past)
			subject(?e, ?s)
			name(?s, 'Mary')
			object(?e, ?o)
			isa(?o, Child)
			determiner(?o, 2)
		";

		$this->doTest($relations, "en", "Mary had 2 children.");
		$this->doTest($relations, "nl", "Mary had 2 kinderen.");
	}

	public function testCopularSentence()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			tense(?e, Past)
			subject(?e, ?s)
			name(?s, 'Ada Lovelace')
			complement(?e, ?c)
			isa(?c, Daughter)
			determiner(?c, The)
			link(Of, ?c, ?p)
			name(?p, 'Lord Byron')
		";

		$this->doTest($relations, "en", "Ada Lovelace was the daughter of Lord Byron.");
		$this->doTest($relations, "nl", "Ada Lovelace was de dochter van Lord Byron.");
	}

	public function testSimpleConjunction()
	{
		//    ?n1
		//   /   \
		//  ?e1  ?e2

		$relations = "
			sentence(?n1)
			link(And, ?n1, ?e1, ?e2)
			name(?e1, 'Ada Lovelace')
			name(?e2, 'Allegra Byron')
		";

		$this->doTest($relations, "en", "Ada Lovelace and Allegra Byron.");
		$this->doTest($relations, "nl", "Ada Lovelace en Allegra Byron.");
	}

	public function testConjunction()
	{
		//    ?n1
		//   /   \
		//  ?e1  ?n2
		//      /   \
		//     ?e2  ?n3
		//         /   \
		//        ?e3   ?e4

		$relations = "
			sentence(?n1)
			link(And, ?n1, ?e1, ?n2)
			link(And, ?n2, ?e2, ?n3)
			link(And, ?n3, ?e3, ?e4)
			name(?e1, 'Joe')
			name(?e2, 'William')
			name(?e3, 'Jack')
			name(?e4, 'Averell')
		";

		$this->doTest($relations, "en", "Joe, William, Jack, and Averell.");
		$this->doTest($relations, "nl", "Joe, William, Jack en Averell.");
	}

	public function testModifierCopularSentence()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			tense(?e, Past)
			subject(?e, ?s)
			name(?s, 'Mary Shelley')
			modifier(?e, ?c)
			isa(?c, Old)
		";

		$this->doTest($relations, "en", "Mary Shelley was old.");
		$this->doTest($relations, "nl", "Mary Shelley was oud.");
	}

	public function testModifierCopularSentenceWithNumeralDeterminer()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			tense(?e, Past)
			subject(?e, ?s)
			name(?s, 'Mary Shelley')
			modifier(?e, ?c)
			isa(?c, Old)
			modifier(?c, ?d)
			determiner(?d, 53)
			isa(?d, Year)
		";

		$this->doTest($relations, "en", "Mary Shelley was 53 years old.");
		$this->doTest($relations, "nl", "Mary Shelley was 53 jaar oud.");
	}

	public function testVerbWithPreposition()
	{
		$relations = "
			sentence(?e)
			mood(?e, Declarative)
			isa(?e, Marry)
			tense(?e, Past)
			aspect(?e, Perfect)
			subject(?e, ?s)
			name(?s, 'Lord Byron')
			link(To, ?e, ?o)
			name(?o, 'Anne Isabella Milbanke')
		";
		$this->doTest($relations, "en", "Lord Byron was married to Anne Isabella Milbanke.");
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

		/** @var RelationList $Sentence */
		$Sentence = $Parser->parse($relations);

		$surfaceRepresentation = $Generator->generate($Grammar = GrammarFactory::getGrammar($language), $Sentence);
		$this->assertSame($expected, $surfaceRepresentation);
	}
}
