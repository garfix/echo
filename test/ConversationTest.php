<?php

namespace agentecho\test;

use \agentecho\AgentEcho;
use \agentecho\test\TestBase;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;

class ConversationTest extends TestBase
{
	public function execute()
	{
		$Echo = new AgentEcho();
		$Echo->addGrammar(new EnglishGrammar());
		$Echo->addGrammar(new DutchGrammar());

		$Conversation = $Echo->startConversation();
		// proper error feedback
		$answer = $Conversation->answer('rwyrwur');
		$this->test(270, $answer, "Word not found: rwyrwur");

		$answer = $Conversation->answer('We rwyrwur born');
		$this->test(271, $answer, "Word not found: rwyrwur");
	}
}