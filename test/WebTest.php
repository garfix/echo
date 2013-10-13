<?php

namespace agentecho\test;

use agentecho\AgentEcho;
use agentecho\component\DataMapper;
use agentecho\component\GrammarFactory;
use agentecho\knowledge\DBPedia;

require_once __DIR__ . '/../Autoload.php';

/**
 * Tests all sentences that can be made with the web interface.
 *
 * @author Patrick van Bergen
 */
class WebTest extends \PHPUnit_Framework_TestCase
{
	public function testWhereWasLordByronBorn()
	{
		$this->assertSame('Lord Byron was born in London.', $this->answer('en', 'Where was "Lord Byron" born?'), 'en');
		$this->assertSame('Lord Byron is geboren in London.', $this->answer('nl', 'Waar is "Lord Byron" geboren?'), 'nl');
	}

	public function testWhenWasLordByronBorn()
	{
		$this->assertSame('Lord Byron was born on January 22, 1788.', $this->answer('en', 'When was "Lord Byron" born?'), 'en');
		$this->assertSame('Lord Byron is geboren op 22 januari 1788.', $this->answer('nl', 'Wanneer is "Lord Byron" geboren?'), 'nl');
	}

	public function testWhereDidLordByronBornDie()
	{
		$this->assertSame('Lord Byron died in Missolonghi.', $this->answer('en', 'Where did "Lord Byron" die?'), 'en');
		$this->assertSame('Lord Byron is gestorven in Missolonghi.', $this->answer('nl', 'Waar is "Lord Byron" gestorven?'), 'nl');
	}

	public function testWhenDidLordByronDie()
	{
		$this->assertSame('Lord Byron died on April 19, 1824.', $this->answer('en', 'When did "Lord Byron" die?'), 'en');
		$this->assertSame('Lord Byron is gestorven op 19 april 1824.', $this->answer('nl', 'Wanneer is "Lord Byron" gestorven?'), 'nl');
	}

	public function testWhoWereAdaLovelacesParents()
	{
		$this->assertSame('Lord Byron and Anne Isabella Byron, Baroness Byron', $this->answer('en', 'Who were "Ada Lovelace" \'s parents?'), 'en');
		$this->assertSame('Lord Byron en Anne Isabella Byron, Baroness Byron', $this->answer('nl', 'Wie waren "Ada Lovelace" \'s ouders?'), 'nl');
	}

	public function testWhoWereLordByronsChildren()
	{
		$this->assertSame('Ada Lovelace and Allegra Byron', $this->answer('en', 'Who were "Lord Byron" \'s children?'), 'en');
		$this->assertSame('Ada Lovelace en Allegra Byron', $this->answer('nl', 'Wie waren "Lord Byron" \'s kinderen?'), 'nl');
	}

	private function answer($language, $question)
	{
		static $Echo = null;

		if ($Echo === null) {

			$Echo = new AgentEcho();
			$Echo->addKnowledgeSource(new DBPedia());
			$Echo->addInterpreter(new DataMapper(__DIR__ . '/../resources/ruleBase1.map'));

		}

		$Echo->setGrammar(GrammarFactory::getGrammar($language));

		return $Echo->answer($question);
	}
}
