<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Relation;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\SentenceBuilder;

class ProductionTest extends TestBase
{
	function execute()
	{
		$Echo = new AgentEcho();
		$Echo->addGrammar($English = new EnglishGrammar());
		$Echo->addGrammar($Dutch = new DutchGrammar());

		$Conversation = $Echo->startConversation();

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
		$line = $Conversation->produce($Sentence);
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
		$Conversation->setCurrentGrammar($English);
		$line = $Conversation->produce($Phrase1);
		$this->test(402, $line, 'John and Mary');

		$line = $Conversation->produce($Phrase2);
		$this->test(403, $line, 'John, Mary, Benjamin, and Bob');

		// Dutch
		$Conversation->setCurrentGrammar($Dutch);
		$line = $Conversation->produce($Phrase1);
		$this->test(404, $line, 'John en Mary');

		$line = $Conversation->produce($Phrase2);
		$this->test(405, $line, 'John, Mary, Benjamin en Bob');
	}
}