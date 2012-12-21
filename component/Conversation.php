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
		$Parser->setProperNounIdentifiers($this->KnowledgeManager);

		try {

			// parse the sentence
			$SentenceContext = $Parser->parseFirstLine($question);

			// update the current grammar from the language found in this sentence
			$this->CurrentGrammar = $Parser->getCurrentGrammar();

#todo: what do we need the context for? is it necessary to keep it at this level of abstraction?

			// extract the Sentence
			$Sentence = $SentenceContext->getRootObject();

$Semantics = $SentenceContext->getSemantics();

			// update the subject of the conversation
			$ContextProcessor = new ContextProcessor();
			$ContextProcessor->updateSubject($Sentence, $this->ConversationContext);

#The subject of a sentence is always S.subject
#$ContextProcessor->updateSemanticSubject($Semantics, $this->ConversationContext);

			// resolve pronouns
			$PronounProcessor = new PronounProcessor();
			$PronounProcessor->replacePronounsByProperNouns($Sentence, $this->ConversationContext);

if (!$Semantics) {
	$Semantics = new \agentecho\datastructure\PredicationList();
}

$PronounProcessor->replaceReferences($Semantics, $this->ConversationContext);
//echo($Semantics);exit;
			// process the sentence
			$SentenceProcessor = new SentenceProcessor($this->KnowledgeManager);
			$Response = $SentenceProcessor->process($Sentence, $Semantics);

			// produce the surface text of the response
			$Producer = new Producer();
			$answer = $Producer->produce($Response, $this->CurrentGrammar);

			// substitute proper nouns by pronouns

		} catch (\Exception $E) {

			$message = $E->getMessage();

			if ($E instanceof EchoException) {
				$translatedMessage = Translations::translate($message, $Parser->getCurrentGrammar()->getLanguageCode());
				$E->setMessage($translatedMessage);
			}

			$answer = (string)$E;
		}

		return $answer;
	}
}