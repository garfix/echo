<?php

namespace agentecho\component;

use \agentecho\AgentEcho;
use \agentecho\Settings;
use \agentecho\grammar\Grammar;
use \agentecho\datastructure\SentenceContext;
use \agentecho\phrasestructure\SentenceBuilder;
use \agentecho\exception\ConfigurationException;
use \agentecho\exception\ParseException;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Conjunction;

/**
 * This class implements a discourse between a user and Echo.
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

	public function isProperNoun($identifier)
	{
		// is $identifier a proper noun in the context?

		// is $identifier a proper noun in one of the knowledge sources?
		$success = $this->Echo->getKnowledgeManager()->isProperNoun($identifier);

		return $success;
	}

	/**
	 * The raw input is parsed into a syntactic / semantic structure.
	 * and these can only be corrected if the most likely grammatical class is known.
	 * The input may be from any of the known languages. While parsing we detect which one.
	 *
	 * @param string $input This input may consist of several sentences, if they are properly separated.
	 * @return array an array of Sentence objects
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	public function parse($input)
	{
		return $this->Echo->getParser()->parseSentenceGivenMultipleGrammars($input, $this, $this->CurrentGrammar, $this->Echo->getAvailableGrammars());
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
		$SentenceContext->phraseSpecification = $phraseSpecification;
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
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @return SentenceContext
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	public function parseFirstLine($input)
	{
		$sentences = $this->parse($input);
		return $sentences ? $sentences[0] : false;
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

			$SentenceContext = $this->parseFirstLine($question);

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