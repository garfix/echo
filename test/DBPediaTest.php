<?php

namespace agentecho\test;

require_once __DIR__ . '/../Autoload.php';

use agentecho\AgentEcho;
use agentecho\component\AgentConfig;
use agentecho\component\DataMapper;
use agentecho\component\GrammarFactory;
use agentecho\knowledge\DBPedia;

/**
 * Question answering
 * Lord Byron facts: http://dbpedia.org/page/Lord_Byron
 * Anne Isabella Milbanke http://dbpedia.org/page/Anne_Isabella_Byron,_Baroness_Byron
 * Mary Shelley http://dbpedia.org/page/Mary_Shelley
 */
class DBPediaTest extends \PHPUnit_Framework_TestCase
{
	private function getAgentConfig()
	{
		$Config = new AgentConfig();

		$Config->addKnowledgeSource(new DBPedia());
		$Config->addInterpreter(new DataMapper(__DIR__ . '/../resources/basic.interpretations'));
		$Config->addGrammar(GrammarFactory::getGrammar('en'));
		$Config->addGrammar(GrammarFactory::getGrammar('nl'));

		return $Config;
	}

	public function testNestedPrepositionalPhraseQuestion()
	{
		$Echo = new AgentEcho($this->getAgentConfig());

		// S => aux NP VP ; DBPedia
		$answer = $Echo->answer("Was Lord Byron influenced by the author of Paradise Lost?");
		$this->assertSame('Yes, Lord Byron was influenced by the author of Paradise Lost.', $answer);
		$answer = $Echo->answer("Werd Lord Byron beïnvloed door de auteur van Paradise Lost?");
		$this->assertSame('Ja, Lord Byron werd beïnvloed door de auteur van Paradise Lost.', $answer);
	}

	public function testQuestionThatRequiresCount()
	{
		$Echo = new AgentEcho($this->getAgentConfig());

		// S => WhNP aux NP VP
		$answer = $Echo->answer("How many children did Lord Byron have?");
		$this->assertSame('Lord Byron had 2 children.', $answer);
		$answer = $Echo->answer("Hoeveel kinderen had Lord Byron?");
		$this->assertSame('Lord Byron had 2 kinderen.', $answer);
	}

	public function testDoubleNounPhraseQuestion()
	{
		$Echo = new AgentEcho($this->getAgentConfig());

		// S => aux NP NP
		$answer = $Echo->answer("Was Ada Lovelace the daughter of Lord Byron?");
		$this->assertSame('Yes, Ada Lovelace was the daughter of Lord Byron.', $answer);
		$answer = $Echo->answer("Was Ada Lovelace een dochter van Lord Byron?");
		$this->assertSame('Ja, Ada Lovelace was een dochter van Lord Byron.', $answer);
	}

	public function testImperativeSentence()
	{
		$Echo = new AgentEcho($this->getAgentConfig());

		// S => VP
		$answer = $Echo->answer("Name Lord Byron's children");
		$this->assertSame("Ada Lovelace and Allegra Byron.", $answer);
		$answer = $Echo->answer("Noem Lord Byron's kinderen");
		$this->assertSame("Ada Lovelace en Allegra Byron.", $answer);
	}

	public function testSymmetricRelationQuestion()
	{
		$Echo = new AgentEcho($this->getAgentConfig());

		// symmetric relations
		$answer = $Echo->answer("Was Lord Byron married to Anne Isabella Milbanke?");
		$this->assertSame("Yes, Lord Byron was married to Anne Isabella Milbanke.", $answer);
		$answer = $Echo->answer("Was Anne Isabella Milbanke married to Lord Byron?");
		$this->assertSame("Yes, Anne Isabella Milbanke was married to Lord Byron.", $answer);
		$answer = $Echo->answer("Was Lord Byron getrouwd met Anne Isabella Milbanke?");
		$this->assertSame("Ja, Lord Byron was getrouwd met Anne Isabella Milbanke.", $answer);
	}

	/**
	 * The answer is calculated.
	 * Uses a dependent clause
	 */
	public function testQuestionThatRequiresCalculation()
	{
		$Config = new AgentConfig();
		$Config->addKnowledgeSource(new DBPedia());
		$Config->addInterpreter(new DataMapper(__DIR__ . '/../resources/basic.interpretations'));
		$Config->addGrammar(GrammarFactory::getGrammar('en'));

		$Echo = new AgentEcho($Config);


		$answer = $Echo->answer("How old was Mary Shelley when she died?");

		$this->assertSame("Mary Shelley was 53 years old.", $answer);

#todo !
#		$this->assertSame("She was 53 years old.", $answer);

	}
}