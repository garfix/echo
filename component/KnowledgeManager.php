<?php

namespace agentecho\component;

use \agentecho\knowledge\KnowledgeSource;
use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\PredicationList;
use agentecho\knowledge\RuleSource;

/**
 * This object provides a single interface for multiple KnowledgeSources.
 */
class KnowledgeManager implements ProperNounIdentifier
{
	/** @var Sources of information that are needed to answer questions */
	private $knowledgeSources = array();

	private $ruleSources = array();

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->knowledgeSources[] = $KnowledgeSource;
	}

	public function getKnowledgeSources()
	{
		return $this->knowledgeSources;
	}

	public function addRuleSource(RuleSource $RuleSource)
	{
		$this->ruleSources[] = $RuleSource;
	}

	public function getRuleSources()
	{
		return $this->ruleSources;
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

	/**
	 * Is $identifier a proper noun in any of the knowledge sources?
	 * @param $identifier
	 * @return bool
	 */
	public function isProperNoun($identifier)
	{
		foreach ($this->knowledgeSources as $KnowledgeSource) {
			$result = $KnowledgeSource->isProperNoun($identifier);
			if ($result) {
				return $result;
			}
		}

		return false;
	}
}