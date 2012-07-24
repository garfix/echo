<?php

namespace agentecho\test;

use \agentecho\test\TestBase;
use \agentecho\component\Producer;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Relation;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\SentenceBuilder;

class ProducerTest extends TestBase
{
	function execute()
	{
		$Producer = new Producer();

		$English = new EnglishGrammar();
		$Dutch = new DutchGrammar();

		$John = new Entity();
			$John->setName('John');
		$Mary = new Entity();
			$Mary->setName('Mary');
		$Flowers = new Entity();
			$Flowers->setCategory('flower');
			$Flowers->setNumber(Entity::PLURAL);
		$Relation = new Relation();
			$Relation->setPredicate('give');
			$Relation->setSubject($John);
			$Relation->setObject($Flowers);
			$Relation->setIndirectObject($Mary);
		$Sentence = new Sentence();
			$Sentence->setRelation($Relation);
		$line = $Producer->produce($Sentence, $English);
		$this->test(401, $line, 'John gives Mary flowers.');

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
		$this->test(402, $line, 'John and Mary');

		$line = $Producer->produce($Phrase2, $English);
		$this->test(403, $line, 'John, Mary, Benjamin, and Bob');

		// Dutch
		$line = $Producer->produce($Phrase1, $Dutch);
		$this->test(404, $line, 'John en Mary');

		$line = $Producer->produce($Phrase2, $Dutch);
		$this->test(405, $line, 'John, Mary, Benjamin en Bob');
	}
}