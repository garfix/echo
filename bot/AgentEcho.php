<?php

require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/language/Conversation.php';
require_once __DIR__ . '/language/KnowledgeManager.php';

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
 * - Configurable: grammars, and knowledge sources, and other dependencies are injected, not hardcoded
 * - Portable to other languages (so: no fancy PHP-specific idiosyncracies)
 *
 * TODO:
 * sentenceType => mood
 * 1st person (in artikel voorbeeld => 3rd person)
 * als je de parse niet kunt maken, geef dan terug wat "waar de fout zit" in de zin
 * namespaces
 * errors: try / catch
 */
class AgentEcho
{
	/** @var A manager for knowledge sources */
	private $KnowledgeManager;

	/** @var Available grammars */
	private $availableGrammars = array();

	public function __construct()
	{
		$this->KnowledgeManager = new KnowledgeManager();
	}

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->KnowledgeManager->addKnowledgeSource($KnowledgeSource);
	}

	public function addGrammar(Grammar $Grammar)
	{
		$this->availableGrammars[] = $Grammar;
	}

	public function getAvailableGrammars()
	{
		return $this->availableGrammars;
	}

	public function getKnowledgeManager()
	{
		return $this->KnowledgeManager;
	}

	public function startConversation()
	{
		$Conversation = new Conversation($this);

		return $Conversation;
	}
}
