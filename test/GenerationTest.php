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
			sentence(?e) and
			mood(?e, Declarative) and
			isa(?e, Walk) and
			subject(?e, ?s) and
			name(?s, 'John')
		";

		$this->doTest($relations, "John walks.");
	}

	public function testVerbWithExplicitPastTense()
	{
		// note: tense is explicitly the past

		$relations = "
			sentence(?e) and
			mood(?e, Declarative) and
			isa(?e, Walk) and
			tense(?e, Past) and
			subject(?e, ?s) and
			name(?s, 'John')
		";

		$this->doTest($relations, "John walked.");
	}

	/**
	 * @param $relations
	 */
	public function doTest($relations, $expected)
	{
		$Parser = new SemanticStructureParser();
		/** @var PredicationList $Sentence */
		$Sentence = $Parser->parse($relations);

		$Generator = new Generator();
		$Grammar = GrammarFactory::getGrammar('en');
		$surfaceRepresentation = $Generator->generate($Grammar, $Sentence);

		$this->assertSame($expected, $surfaceRepresentation);
	}
}
