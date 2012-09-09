<?php

namespace agentecho\component;

use \agentecho\AgentEcho;
use \agentecho\Settings;
use \agentecho\grammar\Grammar;
use \agentecho\phrasestructure\SentenceBuilder;
use \agentecho\exception\ConfigurationException;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Adverb;
use \agentecho\phrasestructure\Date;

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
	 * High-level: reply to the human readable $question with a human readable sentence
	 *
	 * @param string $question
	 * @return string The response
	 */
	public function answer($question)
	{
		$answer = '';

		$Parser = new Parser();
		$Parser->setGrammars($this->Echo->getAvailableGrammars());
		$Parser->setCurrentGrammar($this->CurrentGrammar);
		$Parser->setProperNounIdentifiers($this->Echo->getKnowledgeManager());

		try {

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

					$Adverb = new Adverb();
					$Adverb->setCategory('yes');
					$Sentence->getRelation()->setAdverb($Adverb);

					$Producer = new Producer();

					$Sentence->setSentenceType(Sentence::DECLARATIVE);
					$s = $Producer->produce($Sentence, $this->CurrentGrammar);

					if ($s) {
						$answer = $s;
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

						$found = false;

						// how many?
						if ($Argument2 = $Relation->getArgument2()) {
							if ($Determiner = $Argument2->getDeterminer()) {
								if ($Determiner->isQuestion()) {
									$Sentence->setSentenceType(Sentence::DECLARATIVE);
									$Determiner->setQuestion(false);
									$Determiner->setCategory($answer);

									$found = true;
								}
							}
						}

						// when / where?
						if (!$found) {
							if ($Preposition = $Relation->getPreposition()) {
								if ($Object = $Preposition->getObject()) {
									if ($Object->isQuestion()) {
										if ($Preposition->getCategory() == 'where') {
											$Sentence->setSentenceType(Sentence::DECLARATIVE);
											$Preposition->setCategory('in');
											$Object->setName($answer);
											$Object->setQuestion(false);

											$found = true;
										}
										if ($Preposition->getCategory() == 'when') {
											$Sentence->setSentenceType(Sentence::DECLARATIVE);
											$Preposition->setCategory('on');

											// in stead of "name" create a new Date object
											list($year, $month, $day) = explode('-', $answer);
											$Date = new Date();
											$Date->setYear((int)$year);
											$Date->setMonth((int)$month);
											$Date->setDay((int)$day);
											$Preposition->setObject($Date);

											$found = true;
										}
									}
								}
							}
						}

						if ($found) {
							$Producer = new Producer();
							$sentence = $Producer->produce($Sentence, $this->CurrentGrammar);
							if ($sentence) {
								$answer = $sentence;
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

					$Producer = new Producer();
                    $sentence = $Producer->produce($Phrase, $this->CurrentGrammar);

					if ($sentence) {
						$answer = $sentence;
					}
				}

			} else {
				$answer = 'ok.';
			}

		} catch (\Exception $E) {

			$message = $E->getMessage();
			$translatedMessage = Translations::translate($message, $Parser->getCurrentGrammar()->getLanguageCode());
			$E->setMessage($translatedMessage);

			$answer = (string)$E;
		}

		return $answer;
	}
}