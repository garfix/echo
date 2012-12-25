<?php

namespace agentecho;

// start autoloading based on namespaces
require_once __DIR__ . '/component/Autoload.php';

use \agentecho\component\KnowledgeManager;
use \agentecho\component\Conversation;
use \agentecho\component\Parser;
use \agentecho\knowledge\KnowledgeSource;
use agentecho\knowledge\RuleSource;
use \agentecho\grammar\Grammar;

/**
 * Echo is a conversational agent.
 *
 * Step 1:
 * - Add the knowledge sources and grammars you need.
 * Step 2:
 * - Call startConversation to acquire a conversation context.
 * Step 3:
 * - Call the methods of the Conversation
 *
 * Basic principles:
 * - Easy to learn and use (the user should not need to add stuff that can be preprogrammed)
 * - Fast (use the fastest algorithms known)
 * - Testable (every function needs a unit test)
 * - Provide really helpful error messages
 * - Configurable: grammars, and knowledge sources, and other dependencies are injected, not hardcoded
 * - Portable to other languages (so: no fancy PHP-specific idiosyncracies)
 * - Create independent functions that could be used without the context of the agent (i.e. getPlural($word)
 * - The lexicon should be kept as simple as possible (it is possible to create complex features sets in it, but this often leads to hard to debug problems)
 * - Don't invent meanings. 'Where' means where; not: 'unknown location'. The meaning of cat is cat. A meaning is best described by the word that is normally used for it.
 *
 * Preconditions:
 * - We can ask of the user that he enters grammatically and lexically correct sentences.
 *
 * TODO:
 * sentenceType => mood (later een keer; ben er nog niet uit wat ik hiermee doe)
 * 1st person (in artikel voorbeeld => 3rd person)
 * De zinseinde-detectie is te beperkt (zoeken naar een punt)
 * default feature values (bv. voice=active) bij het uitvoeren van DAG unification
 * Welke mogen weg? features / head / syntax?
 * Haal zo veel mogelijk de rare constructies weg uit het lexicon!
 * Sentence: verplaats voice naar clause
 */
class AgentEcho
{
	/** @var A manager for knowledge sources */
	private $KnowledgeManager;

	/** @var Available grammars */
	private $grammars = array();

	public function __construct()
	{
		$this->KnowledgeManager = new KnowledgeManager();
		$this->Parser = new Parser($this);
	}

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->KnowledgeManager->addKnowledgeSource($KnowledgeSource);
	}

	public function addRuleSource(RuleSource $RuleSource)
	{
		$this->KnowledgeManager->addRuleSource($RuleSource);
	}

	public function addGrammar(Grammar $Grammar)
	{
		$this->grammars[] = $Grammar;
	}

	public function getAvailableGrammars()
	{
		return $this->grammars;
	}

	public function getKnowledgeManager()
	{
		return $this->KnowledgeManager;
	}

	public function getParser()
	{
		return $this->Parser;
	}

	public function startConversation()
	{
		$Conversation = new Conversation($this->getAvailableGrammars(), $this->getKnowledgeManager());

		return $Conversation;
	}
}

function r($string, $return = false)
{
	$trace = debug_backtrace();
	echo $trace[0]['file'] . ' (' . $trace[0]['line'] . '):' . "\n";
	return print_r($string, $return);
}
