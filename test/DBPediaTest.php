<?php

namespace agentecho\test;

require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\AgentEcho;
use \agentecho\knowledge\DBPedia;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\component\Conversation;

/**
 * Question answering
 * Lord Byron facts: http://dbpedia.org/page/Lord_Byron
 * Anne Isabella Milbanke http://dbpedia.org/page/Anne_Isabella_Byron,_Baroness_Byron
 * Mary Shelley http://dbpedia.org/page/Mary_Shelley
 */
class DBPediaTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return Conversation
	 */
	private function startEnglishConversation()
	{
		$Echo = new AgentEcho();
		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addGrammar($English = new EnglishGrammar());

		$Conversation = $Echo->startConversation();

		return $Conversation;
	}

	public function test()
	{
		$Echo = new AgentEcho();
		$Echo->addKnowledgeSource(new DBPedia());
		$Echo->addGrammar($English = new EnglishGrammar());
		$Echo->addGrammar($Dutch = new DutchGrammar());

		$Conversation = $Echo->startConversation();

		// S => aux NP VP ; DBPedia
		$answer = $Conversation->answer("Was Lord Byron influenced by the author of Paradise Lost?");
		$this->assertSame('Yes, Lord Byron was influenced by the author of Paradise Lost.', $answer);
		$answer = $Conversation->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->assertSame('Ja, Lord Byron werd beïnvloed door de auteur van Paradise Lost.', $answer);

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("How many children did Lord Byron have?");
		$this->assertSame('Lord Byron had 2 children.', $answer);
		$answer = $Conversation->answer("Hoeveel kinderen had Lord Byron?");
		$this->assertSame('Lord Byron had 2 kinderen.', $answer);

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("Where was Lord Byron born?");
		$this->assertSame('Lord Byron was born in London.', $answer);
		$answer = $Conversation->answer("Waar werd Lord Byron geboren?");
		$this->assertSame('Lord Byron werd geboren in London.', $answer);

		// S => WhNP aux NP VP
		$answer = $Conversation->answer("When was Lord Byron born?");
		$this->assertSame('Lord Byron was born on January 22, 1788.', $answer);

		$answer = $Conversation->answer("Wanneer werd Lord Byron geboren?");
		$this->assertSame('Lord Byron werd geboren op 22 januari 1788.', $answer);

		$answer = $Conversation->answer("Where did Lord Byron die?");
		$this->assertSame('Lord Byron died in Missolonghi.', $answer);

		// S => aux NP NP
		$answer = $Conversation->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->assertSame('Yes, Ada Lovelace was the daughter of Lord Byron.', $answer);
		$answer = $Conversation->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->assertSame('Ja, Ada Lovelace was een dochter van Lord Byron.', $answer);

		// S => VP
		$answer = $Conversation->answer("Name Lord Byron's children");
		$this->assertSame("Allegra Byron and Ada Lovelace", $answer);
		$answer = $Conversation->answer("Noem Lord Byron's kinderen");
		$this->assertSame("Allegra Byron en Ada Lovelace", $answer);

		// symmetric relations
		$answer = $Conversation->answer("Was Lord Byron married to Anne Isabella Milbanke?");
		$this->assertSame("Yes, Lord Byron was married to Anne Isabella Milbanke.", $answer);
		$answer = $Conversation->answer("Was Anne Isabella Milbanke married to Lord Byron?");
		$this->assertSame("Yes, Anne Isabella Milbanke was married to Lord Byron.", $answer);
		$answer = $Conversation->answer("Was Lord Byron getrouwd met Anne Isabella Milbanke?");
		$this->assertSame("Ja, Lord Byron was getrouwd met Anne Isabella Milbanke.", $answer);

	}

//	/**
//	 * The answer is calculated.
//	 * Uses a dependent clause
//	 */
//	public function testCalculatedAnswer()
//	{
//		$this->markTestSkipped();
//		$Conversation = $this->startEnglishConversation();
//
//		$answer = $Conversation->answer("How old was Mary Shelley when she died?");
//		$this->assertSame("She was xx years old.", $answer);
//	}


		// When did Lord Byron pass away?
		// -> interpret the expression into 'die'
}