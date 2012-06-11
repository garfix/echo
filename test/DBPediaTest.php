<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\knowledge\DBPedia;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;

/**
 * Lord Byron facts: http://dbpedia.org/page/Lord_Byron
 */
class DBPediaTest extends TestBase
{
	function execute()
	{
		$Echo = new AgentEcho();
		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addGrammar(new EnglishGrammar());
		$Echo->addGrammar(new DutchGrammar());

		$Conversation = $Echo->startConversation();

	$single = 1;

	if (!$single) {

		/**
		 * Question answering
		 */


		// S => aux NP VP ; DBPedia
		$answer = $Conversation->answer("Was Lord Byron influenced by the author of Paradise Lost?");
		$this->test(201, $answer, 'Yes. Lord Byron was influenced by the author of Paradise Lost.');

		$answer = $Conversation->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->test(202, $answer, 'Yes.');
//	}
		// S => WhNP aux NP VP
		$answer = $Conversation->answer("How many children did Lord Byron have?");
		$this->test(211, $answer, 'Lord Byron had 2 children.');
//if (!$single) {
		$answer = $Conversation->answer("Hoeveel kinderen had Lord Byron?");
		$this->test(212, $answer, 'Lord Byron had 2 kinderen.');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("Where was Lord Byron born?");
		$this->test(221, $answer, 'Dover');
		$answer = $Conversation->answer("Waar werd Lord Byron geboren?");
		$this->test(222, $answer, 'Dover');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("When was Lord Byron born?");
		$this->test(231, $answer, '1788-01-22');
		$answer = $Conversation->answer("Wanneer werd Lord Byron geboren?");
		$this->test(232, $answer, '1788-01-22');
		$answer = $Conversation->answer("Where did Lord Byron die?");
		$this->test(233, $answer, 'Aetolia-Acarnania');

		// S => aux NP NP
		$answer = $Conversation->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->test(241, $answer, 'Yes.');
		$answer = $Conversation->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->test(242, $answer, 'Yes.');
	}

		// S => VP
		$answer = $Conversation->answer("Name Lord Byron's children");
		$this->test(251, $answer, "Ada Lovelace and Allegra Byron");



	//	$answer = $Conversation->answer("Noem Lord Byron's kinderen");
	//	$this->test(252, $answer, "Ada Lovelace en Allegra Byron");
	}
}