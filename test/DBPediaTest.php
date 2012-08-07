<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\knowledge\DBPedia;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;

/**
 * Question answering
 * Lord Byron facts: http://dbpedia.org/page/Lord_Byron
 * Anne Isabella Milbanke http://dbpedia.org/page/Anne_Isabella_Byron,_Baroness_Byron
 */
class DBPediaTest extends TestBase
{
	function execute()
	{
		$Echo = new AgentEcho();
		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addGrammar($English = new EnglishGrammar());
		$Echo->addGrammar($Dutch = new DutchGrammar());

		$Conversation = $Echo->startConversation();

		// S => aux NP VP ; DBPedia
		$answer = $Conversation->answer("Was Lord Byron influenced by the author of Paradise Lost?");
		$this->test(301, $answer, 'Yes. Lord Byron was influenced by the author of Paradise Lost.');
		$answer = $Conversation->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->test(302, $answer, 'Yes. Lord Byron werd beïnvloed door de auteur van Paradise Lost.');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("How many children did Lord Byron have?");
		$this->test(311, $answer, 'Lord Byron had 2 children.');
		$answer = $Conversation->answer("Hoeveel kinderen had Lord Byron?");
		$this->test(312, $answer, 'Lord Byron had 2 kinderen.');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("Where was Lord Byron born?");
		$this->test(321, $answer, 'Dover');
		$answer = $Conversation->answer("Waar werd Lord Byron geboren?");
		$this->test(322, $answer, 'Dover');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("When was Lord Byron born?");
		$this->test(331, $answer, '1788-01-22');
		$answer = $Conversation->answer("Wanneer werd Lord Byron geboren?");
		$this->test(332, $answer, '1788-01-22');

		$answer = $Conversation->answer("Where did Lord Byron die?");
		$this->test(333, $answer, 'Aetolia-Acarnania');

		// S => aux NP NP
		$answer = $Conversation->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->test(341, $answer, 'Yes.');
		$answer = $Conversation->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->test(342, $answer, 'Yes.');

		// S => VP
		$answer = $Conversation->answer("Name Lord Byron's children");
		$this->test(351, $answer, "Ada Lovelace and Allegra Byron");

		$answer = $Conversation->answer("Noem Lord Byron's kinderen");
		$this->test(352, $answer, "Ada Lovelace en Allegra Byron");


		$Conversation->setCurrentGrammar($English);
		$answer = $Conversation->answer("Was Lord Byron married to Anne Isabella Milbanke?");
		$this->test(361, $answer, "Yes.");

		#todo: geef de foutmelding "word not found" in het nederlands.

//		$answer = $Conversation->answer("Was Lord Byron getrouwd met Anne Isabella Milbanke?");
//		$this->test(362, $answer, "Ja.");
	}
}