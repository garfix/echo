<?php

namespace agentecho\component;

use agentecho\knowledge\KnowledgeSource;
use agentecho\phrasestructure\Sentence;
use agentecho\component\DataMapper;

/**
 * This object provides a single interface for multiple KnowledgeSources.
 */
class KnowledgeManager
{
	/** @var KnowledgeSource[] Sources of information that are needed to answer questions */
	private $knowledgeSources = array();

	/** @var DataMapper[] Rulesets that map semantics to semantics */
	private $elaborators = array();

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

	public function addElaborator(DataMapper $Elaborator)
	{
		$this->elaborators[] = $Elaborator;
	}

	public function getElaborators()
	{
		return $this->elaborators;
	}

    public function checkQuestion(Sentence $Sentence)
   	{
   		foreach ($this->knowledgeSources as $KnowledgeSource) {
   			$result = $KnowledgeSource->checkQuestion($Sentence);
   			if ($result !== false) {
   				return $result;
   			}
   		}

   		return false;
   	}

	public function answerQuestion(Sentence $Sentence)
	{
		foreach ($this->knowledgeSources as $KnowledgeSource) {
			$result = $KnowledgeSource->answerQuestion($Sentence);
			if ($result !== false) {
				return $result;
			}
		}

		return false;
	}
}