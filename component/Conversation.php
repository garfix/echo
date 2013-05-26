<?php

namespace agentecho\component;

use \agentecho\component\KnowledgeManager;
use \agentecho\grammar\Grammar;
use \agentecho\exception\ConfigurationException;
use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\ConversationContext;
use \agentecho\exception\EchoException;

/**
 * This class implements a discourse between a user and Echo.
 *
 * It contains functions that allow the user to interact with the agent at the topmost level: surface text in, surface text out.
 */
class Conversation
{
	/** @var ConversationContext Local memory store for the roles in the conversation */
	private $ConversationContext = null;

	/** @var KnowledgeManager The agent having the conversation */
	private $KnowledgeManager;

	/** @var Start parsing in the last used grammar */
	private $CurrentGrammar = null;

	/**
	 * @throws ConfigurationException
	 */
	public function __construct(array $grammars, KnowledgeManager $KnowledgeManager)
	{
		$this->ConversationContext = new ConversationContext();
		$this->grammars = $grammars;
		$this->KnowledgeManager = $KnowledgeManager;

		if (empty($grammars)) {
			throw new ConfigurationException(ConfigurationException::NO_GRAMMAR);
		}

		$Grammar = reset($grammars);
		$this->setCurrentGrammar($Grammar);
	}

	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->CurrentGrammar = $Grammar;
	}

	/**
	 * High-level: reply to the human readable $question with a human readable sentence
	 *
	 * @param string $question
	 * @return string The response
	 */
	public function answer($question)
	{
		// prepare the parser
		$Parser = new Parser();
		$Parser->setGrammars($this->grammars);
		$Parser->setCurrentGrammar($this->CurrentGrammar);

		try {

			// parse the sentence
			$SentenceContext = $Parser->parseFirstLine($question);

			// update the current grammar from the language found in this sentence
			$this->CurrentGrammar = $Parser->getCurrentGrammar();

			// extract the Sentence
			$Sentence = $SentenceContext->getRootObject();

			// extract semantics
			$Semantics = $SentenceContext->getSemantics();

			// update the subject of the conversation
			$ContextProcessor = new ContextProcessor();
			$ContextProcessor->updateSubject($Sentence, $this->ConversationContext);

			// resolve pronouns
			$PronounProcessor = new PronounProcessor();
			$PronounProcessor->replacePronounsByProperNouns($Sentence, $this->ConversationContext);
$a = (string)$Semantics;
			// replace references
			$PronounProcessor->replaceReferences($Semantics, $this->ConversationContext);

$b = (string)$Semantics;

			// process the sentence
			$SentenceProcessor = new SentenceProcessor($this->KnowledgeManager);
			$Response = $SentenceProcessor->process($Sentence, $Semantics);

			// produce the surface text of the response
			$Producer = new Producer();
			$answer = $Producer->produce($Response, $this->CurrentGrammar);

			// substitute proper nouns by pronouns
#todo

		} catch (EchoException $E) {

			if ($E instanceof EchoException) {
				$translatedMessage = Translations::translate($E->getMessageText(), $Parser->getCurrentGrammar()->getLanguageCode());
				$E->setMessageText($translatedMessage);
				$E->buildMessage();
			}

			$answer = $E->getMessage();
		}

		return $answer;
	}
}