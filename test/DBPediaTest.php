<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\AgentEcho;
use agentecho\component\DataMapper;
use agentecho\component\GrammarFactory;
use agentecho\knowledge\DBPedia;
use agentecho\grammar\EnglishGrammar;
use agentecho\grammar\DutchGrammar;

/**
 * Question answering
 * Lord Byron facts: http://dbpedia.org/page/Lord_Byron
 * Anne Isabella Milbanke http://dbpedia.org/page/Anne_Isabella_Byron,_Baroness_Byron
 * Mary Shelley http://dbpedia.org/page/Mary_Shelley
 */
class DBPediaTest extends \PHPUnit_Framework_TestCase
{
	private function getConversation()
	{
		$Echo = new AgentEcho();
		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addInterpreter(new DataMapper(__DIR__ . '/../resources/ruleBase1.map'));
		$Echo->addGrammar(GrammarFactory::getGrammar('en'));
		$Echo->addGrammar(GrammarFactory::getGrammar('nl'));

		$Conversation = $Echo->startConversation();

		return $Conversation;
	}

	public function test1()
	{
		$Conversation = $this->getConversation();

		// S => aux NP VP ; DBPedia
		$answer = $Conversation->answer("Was Lord Byron influenced by the author of Paradise Lost?");
		$this->assertSame('Yes, Lord Byron was influenced by the author of Paradise Lost.', $answer);
		$answer = $Conversation->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->assertSame('Ja, Lord Byron werd beïnvloed door de auteur van Paradise Lost.', $answer);
	}

	public function test2()
	{
		$Conversation = $this->getConversation();

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("How many children did Lord Byron have?");
		$this->assertSame('Lord Byron had 2 children.', $answer);
		$answer = $Conversation->answer("Hoeveel kinderen had Lord Byron?");
		$this->assertSame('Lord Byron had 2 kinderen.', $answer);
	}

	public function test3()
	{
		$Conversation = $this->getConversation();

		// S => aux NP NP
		$answer = $Conversation->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->assertSame('Yes, Ada Lovelace was the daughter of Lord Byron.', $answer);
		$answer = $Conversation->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->assertSame('Ja, Ada Lovelace was een dochter van Lord Byron.', $answer);
	}

	public function test4()
	{
		$Conversation = $this->getConversation();

		// S => VP
		$answer = $Conversation->answer("Name Lord Byron's children");
		$this->assertSame("Ada Lovelace and Allegra Byron.", $answer);
		$answer = $Conversation->answer("Noem Lord Byron's kinderen");
		$this->assertSame("Ada Lovelace en Allegra Byron.", $answer);
	}

	public function test5()
	{
		$Conversation = $this->getConversation();

		// symmetric relations
		$answer = $Conversation->answer("Was Lord Byron married to Anne Isabella Milbanke?");
		$this->assertSame("Yes, Lord Byron was married to Anne Isabella Milbanke.", $answer);
		$answer = $Conversation->answer("Was Anne Isabella Milbanke married to Lord Byron?");
		$this->assertSame("Yes, Anne Isabella Milbanke was married to Lord Byron.", $answer);
		$answer = $Conversation->answer("Was Lord Byron getrouwd met Anne Isabella Milbanke?");
		$this->assertSame("Ja, Lord Byron was getrouwd met Anne Isabella Milbanke.", $answer);
	}

	/**
	 * The answer is calculated.
	 * Uses a dependent clause
	 */
	public function testCalculatedAnswer()
	{
		$Echo = new AgentEcho();
		$Echo->addGrammar($English = GrammarFactory::getGrammar('en'));

		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addInterpreter(new DataMapper(__DIR__ . '/../resources/ruleBase1.map'));

		$Conversation = $Echo->startConversation();

		$answer = $Conversation->answer("How old was Mary Shelley when she died?");

		$this->assertSame("Mary Shelley was 53 years old.", $answer);

#todo !
#		$this->assertSame("She was 53 years old.", $answer);

	}
}