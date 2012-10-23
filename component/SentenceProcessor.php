<?php

namespace agentecho\component;

use \agentecho\component\KnowledgeManager;
use agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Adverb;
use \agentecho\phrasestructure\Date;
use \agentecho\phrasestructure\SentenceBuilder;

/**
 * This class answers question and processes imperatives.
 *
 * @author Patrick van Bergen
 */
class SentenceProcessor
{
	/** @var KnowledgeManager The agent having the conversation */
	private $KnowledgeManager;

	public function __construct(KnowledgeManager $KnowledgeManager)
	{
		$this->KnowledgeManager = $KnowledgeManager;
	}

	/**
	 * @param Sentence $Sentence
	 * @return PhraseStructure
	 */
	public function process(Sentence $Sentence)
	{
		$Answer = null;

		$sentenceType = $Sentence->getSentenceType();
		if ($sentenceType == 'yes-no-question') {

			// since this is a yes-no question, check the statement
			$result = $this->KnowledgeManager->checkQuestion($Sentence);

			if ($result) {
				$Answer = $Sentence;

				$Adverb = new Adverb();
				$Adverb->setCategory('yes');
				$Answer->getRelation()->setAdverb($Adverb);
				$Answer->setSentenceType(Sentence::DECLARATIVE);
			}

		} elseif ($sentenceType == 'wh-question') {

			$answer = $this->KnowledgeManager->answerQuestion($Sentence);

			// incorporate the answer in the original question
			if ($answer !== false) {

				$Answer = $Sentence;

				#todo: this should be made more generic

				if ($Relation = $Answer->getRelation()) {

					$found = false;

					// how many?
					if ($Argument2 = $Relation->getArgument2()) {
						if ($Determiner = $Argument2->getDeterminer()) {
							if ($Determiner->isQuestion()) {
								$Answer->setSentenceType(Sentence::DECLARATIVE);
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
										$Answer->setSentenceType(Sentence::DECLARATIVE);
										$Preposition->setCategory('in');
										$Object->setName($answer);
										$Object->setQuestion(false);
									}
									if ($Preposition->getCategory() == 'when') {
										$Answer->setSentenceType(Sentence::DECLARATIVE);
										$Preposition->setCategory('on');

										// in stead of "name" create a new Date object
										list($year, $month, $day) = explode('-', $answer);
										$Date = new Date();
										$Date->setYear((int)$year);
										$Date->setMonth((int)$month);
										$Date->setDay((int)$day);
										$Preposition->setObject($Date);
									}
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

				$answer = $this->KnowledgeManager->answerQuestion($Sentence);

				$entities = array();

				foreach ($answer as $name) {

                       $Entity = new Entity();
                       $Entity->setName($name);

                       $entities[] = $Entity;
				}

				$Answer = SentenceBuilder::buildConjunction($entities);

			}

		}

		return $Answer;
	}
}
