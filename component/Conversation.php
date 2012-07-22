<?php

namespace agentecho\component;

use \agentecho\AgentEcho;
use \agentecho\Settings;
use \agentecho\grammar\Grammar;
use \agentecho\datastructure\SentenceContext;
use \agentecho\phrasestructure\SentenceBuilder;
use \agentecho\exception\ConfigurationException;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;

/**
 * This class implements a discourse between a user and Echo.
 *
 * It contains functions that allow the user to interact with the agent at the topmost level: surface text in, surface text out.
 */
class Conversation
{
	/** @var Local memory store for the roles in the conversation */
	private $context = array();

	/** @var \AgentEcho The agent having the conversation */
	private $Echo;

	/** @var Start parsing in the last used grammar */
	private $CurrentGrammar = null;

	/**
	 * @param \agentecho\AgentEcho $Echo
	 * @throws ConfigurationException
	 */
	public function __construct(AgentEcho $Echo)
	{
		$this->Echo = $Echo;

		// set current grammar
		$availableGrammars = $Echo->getAvailableGrammars();

		if (empty($availableGrammars)) {
			throw new ConfigurationException(ConfigurationException::NO_GRAMMAR);
		}

		$Grammar = reset($availableGrammars);
		$this->setCurrentGrammar($Grammar);
	}

	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->CurrentGrammar = $Grammar;
	}

	/**
	 * Turns an object structure of a phrase or sentence into surface text,
	 *
	 * @param PhraseStructure $Structure
	 * @return string
	 */
	public function produce(PhraseStructure $Structure)
	{
		if ($Structure instanceof Sentence) {
			$phraseSpecification = array('head' => $this->buildPhraseStructure($Structure));
		} else {
			$phraseSpecification = array('head' => array('sem' => $this->buildPhraseStructure($Structure)));
		}

        $SentenceContext = new SentenceContext($this);
		$SentenceContext->setPhraseSpecification($phraseSpecification);
        $SentenceContext->RootObject = $Structure;

        return $this->CurrentGrammar->generate($SentenceContext);
	}

	/**
	 * Turns a phrase object structure into an array structure.
	 *
	 * @param PhraseStructure $PhraseStructure
	 * @return array
	 */
	private function buildPhraseStructure(PhraseStructure $PhraseStructure)
	{
		$structure = array();
		$structure['type'] = strtolower(basename(str_replace('\\', '/', get_class($PhraseStructure))));

		foreach ($PhraseStructure->getAttributes() as $name => $value) {
			if ($value instanceof PhraseStructure) {
				$structure[strtolower($name)] = $this->buildPhraseStructure($value);
			} else {
				$structure[$name] = $value;
			}
		}

		return $structure;
	}

	/**
	 * High-level: reply to the human readable $question with a human readable sentence
	 *
	 * @param string $question
	 * @return string The response
	 */
	public function answer($question)
	{
		$answer = '';

		try {

			$Parser = new Parser();
			$Parser->setGrammars($this->Echo->getAvailableGrammars());
			$Parser->setCurrentGrammar($this->CurrentGrammar);
			$Parser->setProperNounIdentifiers($this->Echo->getKnowledgeManager());

			$SentenceContext = $Parser->parseFirstLine($question);
			$this->CurrentGrammar = $Parser->getCurrentGrammar();

			/** @var Sentence $Sentence  */
			$Sentence = $SentenceContext->getRootObject();

			$sentenceType = $Sentence->getSentenceType();

			if ($sentenceType == 'yes-no-question') {

				// since this is a yes-no question, check the statement
				$result = $this->Echo->getKnowledgeManager()->checkQuestion($Sentence);

				if ($result) {
					$answer = 'Yes.';


					$Sentence->setSentenceType(Sentence::DECLARATIVE);
					$s = $this->produce($Sentence);

					if ($s) {
						$answer .= ' ' . $s;
					}

				} else {
					$answer = 'No.';
				}

			} elseif ($sentenceType == 'wh-question') {

				$answer = $this->Echo->getKnowledgeManager()->answerQuestionAboutObject($Sentence);

				// incorporate the answer in the original question
				if ($answer !== false) {

					#todo: this should be made more generic
//r($Sentence);
					if ($Relation = $Sentence->getRelation()) {
						if ($Argument2 = $Relation->getArgument2()) {
							if ($Determiner = $Argument2->getDeterminer()) {
								if ($Determiner->isQuestion()) {
									$Sentence->setSentenceType(Sentence::DECLARATIVE);
									$Determiner->setQuestion(false);
									$Determiner->setCategory($answer);
//r($Sentence);
//r($phraseSpecification = $this->buildPhraseStructure($Sentence));
									$sentence = $this->produce($Sentence);
									if ($sentence) {
										$answer = $sentence;
									}
								}
							}
						}
					}

				}

			} elseif ($Sentence->getSentenceType() == 'imperative') {

				#todo Imperatives are not always questions
				$isQuestion = true;

				if ($isQuestion) {

					$answer = $this->Echo->getKnowledgeManager()->answerQuestion($Sentence);

					$entities = array();

					foreach ($answer as $name) {

                        $Entity = new Entity();
                        $Entity->setName($name);

                        $entities[] = $Entity;
					}

                    $Phrase = SentenceBuilder::buildConjunction($entities);

                    $sentence = $this->produce($Phrase);

					if ($sentence) {
						$answer = $sentence;
					}
				}

			} else {
				$answer = 'ok.';
			}

		} catch (\Exception $E) {

			$answer = (string)$E;

		}

		return $answer;
	}
}