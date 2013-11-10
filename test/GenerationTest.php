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
		/** @var PredicationList $Sentence */

		$relations = "
			sentence(?e) and
			mood(?e, Declarative) and
			isa(?e, Walk) and
			subject(?e, ?s) and
			name(?s, 'John')
		";

		$Parser = new SemanticStructureParser();
		$Sentence = $Parser->parse($relations);

		$Generator = new Generator();
		$Grammar = GrammarFactory::getGrammar('en');
		$surfaceRepresentation = $Generator->generate($Grammar, $Sentence);

		$this->assertSame("John walks.", $surfaceRepresentation);
	}
}
