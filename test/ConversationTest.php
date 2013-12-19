<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\AgentEcho;
use agentecho\component\GrammarFactory;

class ConversationTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$Echo = new AgentEcho();
		$Echo->addGrammar(GrammarFactory::getGrammar('en'));
		$Echo->addGrammar(GrammarFactory::getGrammar('nl'));

		$Conversation = $Echo->startConversation();
		// proper error feedback
		$answer = $Conversation->answer('rwyrwur');
		$this->assertSame('Word not found: rwyrwur', $answer);

		$answer = $Conversation->answer('We rwyrwur born');
		$this->assertSame('Word not found: rwyrwur', $answer);
	}
}