<?php

namespace agentecho\component;

use agentecho\component\events\EventManager;
use agentecho\knowledge\KnowledgeSource;
use agentecho\grammar\Grammar;

/**
 * This class contains all configurable options of an agent.
 *
 * @author Patrick van Bergen
 */
class AgentConfig
{
	/** @var KnowledgeManager A manager for knowledge sources */
	private $KnowledgeManager;

	/** @var EventManager $EventManager */
	private $EventManager;

	/** @var Grammar[] $grammars A list of grammars that are used to parse a sentence */
	private $grammars = array();

	public function __construct()
	{
		$this->KnowledgeManager = new KnowledgeManager();
		$this->EventManager = new EventManager();
	}

	/**
	 * @return EventManager
	 */
	public function getEventManager()
	{
		return $this->EventManager;
	}

	/**
	 * @return KnowledgeManager
	 */
	public function getKnowledgeManager()
	{
		return $this->KnowledgeManager;
	}

	/**
	 * @return Grammar[]
	 */
	public function getGrammars()
	{
		return $this->grammars;
	}

	/**
	 * Show the agent where to find the data.
	 * A knowledge source is a wrapper around a database.
	 *
	 * @param KnowledgeSource $KnowledgeSource
	 */
	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->KnowledgeManager->addKnowledgeSource($KnowledgeSource);
	}

	/**
	 * Show the agent how to interpret raw semantic data by using the expressions people use.
	 *
	 * @param DataMapper $Interpreter
	 */
	public function addInterpreter(DataMapper $Interpreter)
	{
		$this->KnowledgeManager->addInterpreter($Interpreter);
	}

	/**
	 * @param array $grammars
	 */
	public function setGrammars(array $grammars)
	{
		$this->grammars = $grammars;
	}

	/**
	 * @param Grammar $Grammar
	 */
	public function addGrammar(Grammar $Grammar)
	{
		$this->grammars[] = $Grammar;
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
