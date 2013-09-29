<?php

namespace agentecho\test;

require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\AgentEcho;
use agentecho\component\GrammarFactory;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;

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
		$this->assertSame('Could not parse the part that starts with "rwyrwur"', $answer);

		$answer = $Conversation->answer('We rwyrwur born');
		$this->assertSame('Could not parse the part that starts with "We rwyrwur born"', $answer);
	}
}