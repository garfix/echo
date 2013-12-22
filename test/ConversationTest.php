<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\AgentEcho;
use agentecho\component\AgentConfig;
use agentecho\component\GrammarFactory;

class ConversationTest extends \PHPUnit_Framework_TestCase
{
	public function test()
	{
		$Config = new AgentConfig();
		$Config->setGrammars([GrammarFactory::getGrammar('en'), GrammarFactory::getGrammar('nl')]);

		$Echo = new AgentEcho($Config);

		// proper error feedback
		$answer = $Echo->answer('rwyrwur');
		$this->assertSame('Word not found: rwyrwur', $answer);

		$answer = $Echo->answer('We rwyrwur born');
		$this->assertSame('Word not found: rwyrwur', $answer);
	}
}