<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Relation;
use \agentecho\phrasestructure\Entity;

class ProductionTest extends TestBase
{
	function execute()
	{
		$Echo = new AgentEcho();
		$Echo->addGrammar(new EnglishGrammar());

		$Conversation = $Echo->startConversation();

		$John = new Entity();
			$John->setName('John');
		$Mary = new Entity();
			$Mary->setName('Mary');
		$Flowers = new Entity();
			$Flowers->setCategory('*flower');
			$Flowers->setNumber(Entity::PLURAL);
		$Relation = new Relation();
			$Relation->setPredicate('*give');
			$Relation->setSubject($John);
			$Relation->setObject($Flowers);
			$Relation->setIndirectObject($Mary);
		$Sentence = new Sentence();
			$Sentence->setRelation($Relation);
		$line = $Conversation->produce($Sentence);
		$this->test(401, $line, 'John gives Mary flowers.');
	}
}