<?php

namespace agentecho;

use agentecho\component\AgentConfig;
use agentecho\component\EventManager;
use agentecho\component\KnowledgeManager;
use agentecho\component\Conversation;
use agentecho\component\Parser;
use agentecho\component\SentenceProcessor;
use agentecho\grammar\Grammar;

/**
 * Echo is a natural language interface agent.
 */
class AgentEcho
{
	/** @var AgentConfig $Config */
	private $Config;

	/** @var Conversation $Conversation */
	private $Conversation;

	public function __construct(AgentConfig $Config)
	{
		$this->Config = $Config;

		$this->Conversation = new Conversation($this->Config->getGrammars());
	}

	/**
	 * @param Grammar $Grammar
	 */
	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->Conversation->setCurrentGrammar($Grammar);
	}

	/**
	 * Asks the agent to respond to $question, without a given context of conversation.
	 *
	 * @param $question
	 * @return string An answer
	 */
	public function answer($question)
	{
		$SentenceProcessor = new SentenceProcessor($this->Config->getKnowledgeManager());
		$SentenceProcessor->setEventManager($this->Config->getEventManager());
		return $SentenceProcessor->reply($question, $this->Conversation);
	}
}
