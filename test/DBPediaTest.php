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
		$this->test(301, $answer, 'Yes, Lord Byron was influenced by the author of Paradise Lost.');
		$answer = $Conversation->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->test(302, $answer, 'Ja, Lord Byron werd beïnvloed door de auteur van Paradise Lost.');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("How many children did Lord Byron have?");
		$this->test(311, $answer, 'Lord Byron had 2 children.');
		$answer = $Conversation->answer("Hoeveel kinderen had Lord Byron?");
		$this->test(312, $answer, 'Lord Byron had 2 kinderen.');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("Where was Lord Byron born?");
		$this->test(321, $answer, 'London');
		$answer = $Conversation->answer("Waar werd Lord Byron geboren?");
		$this->test(322, $answer, 'London');

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("When was Lord Byron born?");
		$this->test(331, $answer, '1788-01-22');
		$answer = $Conversation->answer("Wanneer werd Lord Byron geboren?");
		$this->test(332, $answer, '1788-01-22');

		$answer = $Conversation->answer("Where did Lord Byron die?");
		$this->test(333, $answer, 'Missolonghi');

		// S => aux NP NP
		$answer = $Conversation->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->test(341, $answer, 'Yes, Ada Lovelace was the daughter of Lord Byron.');
		$answer = $Conversation->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->test(342, $answer, 'Ja, Ada Lovelace was een dochter van Lord Byron.');

		// S => VP
		$answer = $Conversation->answer("Name Lord Byron's children");
		$this->test(351, $answer, "Allegra Byron and Ada Lovelace");

		$answer = $Conversation->answer("Noem Lord Byron's kinderen");
		$this->test(352, $answer, "Allegra Byron en Ada Lovelace");

		// symmetric relations
		$answer = $Conversation->answer("Was Lord Byron married to Anne Isabella Milbanke?");
		$this->test(361, $answer, "Yes, Lord Byron was married to Anne Isabella Milbanke.");

		$answer = $Conversation->answer("Was Anne Isabella Milbanke married to Lord Byron?");
		$this->test(362, $answer, "Yes, Anne Isabella Milbanke was married to Lord Byron.");

		$answer = $Conversation->answer("Was Lord Byron getrouwd met Anne Isabella Milbanke?");
		$this->test(363, $answer, "Ja, Lord Byron was getrouwd met Anne Isabella Milbanke.");
	}
}