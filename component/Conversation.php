<?php

namespace agentecho\component;

use agentecho\grammar\Grammar;
use agentecho\exception\ConfigurationException;

/**
 * This class implements a discourse between a user and Echo.
 *
 * It contains functions that allow the user to interact with the agent at the topmost level: surface text in, surface text out.
 */
class Conversation
{
	/** @var KnowledgeManager The agent having the conversation */
	private $KnowledgeManager;

	/** @var Parser */
	private $Parser = null;

	/**
	 * @throws ConfigurationException
	 */
	public function __construct(KnowledgeManager $KnowledgeManager, Parser $Parser)
	{
		$this->KnowledgeManager = $KnowledgeManager;
		$this->Parser = $Parser;
	}

	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->Parser->setCurrentGrammar($Grammar);
	}

	/**
	 * High-level: reply to the human readable $question with a human readable sentence
	 *
	 * @param string $question
	 * @return string The response
	 */
	public function answer($question)
	{
		$SentenceProcessor = new SentenceProcessor($this->KnowledgeManager);

		if (isset($this->EventManager)) {
			$SentenceProcessor->setEventManager($this->EventManager);
		}

		$answer = $SentenceProcessor->reply($question, $this->Parser);

		return $answer;
	}
}