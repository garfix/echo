<?php

namespace agentecho;

// start autoloading based on namespaces
require_once __DIR__ . '/component/Autoload.php';

use \agentecho\component\KnowledgeManager;
use \agentecho\component\Conversation;
use \agentecho\knowledge\KnowledgeSource;
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
 *
 * Preconditions:
 * - We can ask of the user that he enters grammatically and lexically correct sentences.
 *
 * TODO:
 * sentenceType => mood
 * 1st person (in artikel voorbeeld => 3rd person)
 * als je de parse niet kunt maken, geef dan terug wat "waar de fout zit" in de zin
 * De zinseinde-detectie is te beperkt (zoeken naar een punt)
 * een "Conversation" is noch een datastructure noc een component, splits hem op in twee delen
 * default feature values (bv. voice=active) bij het uitvoeren van DAG unification
 * language production: sigma => NP ?
 * zorg ervoor dat de excepties in de huidige taal gezet zijn
 * Bouw een OOP laag om de phrase specification array structuur heen. De gebruiker werkt alleen met objecten
 * Proper names worden nu 7x ge-preg matched, steeds met kortere groepen woorden. dit is niet nodig. 1x is genoeg
 * Verander 's' en 'p' in 'singular' en 'plural'
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
	}

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->KnowledgeManager->addKnowledgeSource($KnowledgeSource);
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

	public function startConversation()
	{
		$Conversation = new Conversation($this);

		return $Conversation;
	}
}
