<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\component\GrammarFactory;
use \agentecho\component\Producer;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Clause;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\SentenceBuilder;

class ProducerTest extends \PHPUnit_Framework_TestCase
{
	function testConjunction()
	{
		$Producer = new Producer();

		$English = GrammarFactory::getGrammar('en');
		$Dutch = GrammarFactory::getGrammar('nl');

		$John = new Entity();
			$John->setName('John');
		$Mary = new Entity();
			$Mary->setName('Mary');
		$Flowers = new Entity();
			$Flowers->setCategory('flower');
			$Flowers->setNumber(Entity::PLURAL);
		$Clause = new Clause();
			$Clause->setPredicate('give');
			$Clause->setSubject($John);
			$Clause->setObject($Flowers);
			$Clause->setIndirectObject($Mary);
		$Sentence = new Sentence();
			$Sentence->setClause($Clause);
		$line = $Producer->produce($Sentence, $English);
		$this->assertSame('John gives Mary flowers.', $line);

		$John = new Entity();
			$John->setName('John');
		$Mary = new Entity();
			$Mary->setName('Mary');
		$Benjamin = new Entity();
			$Benjamin->setName('Benjamin');
		$Bob = new Entity();
			$Bob->setName('Bob');
		$Phrase1 = SentenceBuilder::buildConjunction(array($John, $Mary));
		$Phrase2 = SentenceBuilder::buildConjunction(array($John, $Mary, $Benjamin, $Bob));

		// English
		$line = $Producer->produce($Phrase1, $English);
		$this->assertSame('John and Mary', $line);

		$line = $Producer->produce($Phrase2, $English);
		$this->assertSame('John, Mary, Benjamin, and Bob', $line);

		// Dutch
		$line = $Producer->produce($Phrase1, $Dutch);
		$this->assertSame('John en Mary', $line);

		$line = $Producer->produce($Phrase2, $Dutch);
		$this->assertSame('John, Mary, Benjamin en Bob', $line);
	}
}