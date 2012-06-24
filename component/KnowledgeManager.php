<?php

namespace agentecho\component;

use \agentecho\knowledge\KnowledgeSource;
use \agentecho\phrasestructure\Sentence;

/**
 * This object provides a single interface for multiple KnowledgeSources.
 */
class KnowledgeManager
{
	/** @var Sources of information that are needed to answer questions */
	private $knowledgeSources = array();

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->knowledgeSources[] = $KnowledgeSource;
	}

	public function check($phraseSpecification, $sentenceType)
	{
		foreach ($this->knowledgeSources as $KnowledgeSource) {
			$result = $KnowledgeSource->check($phraseSpecification, $sentenceType);
			if ($result !== false) {
				return $result;
			}
		}

		return false;
	}

	public function answerQuestionAboutObject($phraseSpecification, $sentenceType)
	{
		foreach ($this->knowledgeSources as $KnowledgeSource) {
			$result = $KnowledgeSource->answerQuestionAboutObject($phraseSpecification, $sentenceType);
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