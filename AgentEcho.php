<?php

namespace agentecho;

// start autoloading based on namespaces
require_once __DIR__ . '/component/Autoload.php';

use agentecho\component\EventManager;
use agentecho\component\KnowledgeManager;
use agentecho\component\Conversation;
use agentecho\component\Parser;
use agentecho\component\SentenceProcessor;
use agentecho\datastructure\ConversationContext;
use agentecho\knowledge\KnowledgeSource;
use agentecho\knowledge\RuleSource;
use agentecho\grammar\Grammar;
use agentecho\component\DataMapper;

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
 */
class AgentEcho
{
	/** @var KnowledgeManager A manager for knowledge sources */
	private $KnowledgeManager;

	/** @var Parser */
	private $Parser;

	/** @var EventManager $EventManager */
	private $EventManager;

	public function __construct()
	{
		// build the components used by the agent
		$this->KnowledgeManager = new KnowledgeManager();
		$this->Parser = new Parser();
		$this->EventManager = new EventManager();

		// set optional dependencies
		$this->Parser->setEventManager($this->EventManager);
	}

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->KnowledgeManager->addKnowledgeSource($KnowledgeSource);
	}

	public function addElaborator(DataMapper $Elaborator)
	{
		$this->KnowledgeManager->addElaborator($Elaborator);
	}

	public function addGrammar(Grammar $Grammar)
	{
		$this->Parser->addGrammar($Grammar);
	}

	/**
	 * Sets the active grammar,
	 * removes any previously available grammars.
	 *
	 * @param Grammar $Grammar
	 */
	public function setGrammar(Grammar $Grammar)
	{
		$this->Parser->setGrammars(array($Grammar));
	}

	/**
	 * Starts a new conversation.
	 * All interactions within this conversation use the same conversation context.
	 *
	 * @return Conversation
	 */
	public function startConversation()
	{
		$Conversation = new Conversation($this->KnowledgeManager, $this->Parser);

		return $Conversation;
	}

	/**
	 * Asks the agent to respond to $question, without a given context of conversation.
	 *
	 * @param $question
	 * @return string An answer
	 */
	public function answer($question)
	{
		$SentenceProcessor = new SentenceProcessor($this->KnowledgeManager);
		$SentenceProcessor->setEventManager($this->EventManager);
		$ConversationContext = new ConversationContext();
		return $SentenceProcessor->reply($question, $ConversationContext, $this->Parser);
	}

	/**
	 * Add a callback function that will be called whenever an agent event occurs.
	 *
	 * @param callable $listener
	 */
	public function addListener(callable $listener)
	{
		$this->EventManager->addListener($listener);
	}
}

function r($string, $return = false)
{
	$trace = debug_backtrace();
	echo $trace[0]['file'] . ' (' . $trace[0]['line'] . '):' . "\n";
	return print_r($string, $return);
}
