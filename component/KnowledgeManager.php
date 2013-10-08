<?php

namespace agentecho\component;

use agentecho\knowledge\KnowledgeSource;
use agentecho\phrasestructure\Sentence;
use agentecho\component\DataMapper;

/**
 * This object provides a single interface for multiple knowledge sources and data mappers.
 */
class KnowledgeManager
{
	/** @var KnowledgeSource[] Sources of information that are needed to answer questions */
	private $knowledgeSources = array();

	/** @var DataMapper[] Rulesets that map semantics to semantics */
	private $interpreters = array();

	/**
	 * @param KnowledgeSource $KnowledgeSource
	 */
	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->knowledgeSources[] = $KnowledgeSource;
	}

	/**
	 * @return KnowledgeSource[]
	 */
	public function getKnowledgeSources()
	{
		return $this->knowledgeSources;
	}

	/**
	 * @param DataMapper $Interpreter
	 */
	public function addInterpreter(DataMapper $Interpreter)
	{
		$this->interpreters[] = $Interpreter;
	}

	/**
	 * @return DataMapper[]
	 */
	public function getInterpreters()
	{
		return $this->interpreters;
	}
}