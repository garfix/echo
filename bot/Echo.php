<?php

require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/language/Conversation.php';

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
 */
class Echo1
{
	/** @var Sources of information that are needed to answer questions */
	private $knowledgeSources = array();

	/** @var Available grammars */
	private $availableGrammars = array();

	public function __construct()
	{
	}

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->knowledgeSources[] = $KnowledgeSource;
	}

	public function addGrammar(Grammar $Grammar)
	{
		$this->availableGrammars[] = $Grammar;
	}

	public function getAvailableGrammars()
	{
		return $this->availableGrammars;
	}

	public function startConversation()
	{
		$Conversation = new Conversation($this);

		if (!empty($this->availableGrammars)) {
			$Grammar = reset($this->availableGrammars);
		} else {
			$Grammar = null;
		}

		$Conversation->setCurrentGrammar($Grammar);

		return $Conversation;
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
}
