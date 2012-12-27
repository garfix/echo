<?php

namespace agentecho\component;

use \agentecho\component\KnowledgeManager;
use agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Adverb;
use \agentecho\phrasestructure\Date;
use \agentecho\phrasestructure\SentenceBuilder;
use \agentecho\datastructure\PredicationList;
use agentecho\component\InferenceEngine;
use agentecho\knowledge\PredicationListKnowledgeSource;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Property;
use agentecho\datastructure\Variable;

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
	public function process(Sentence $Sentence, PredicationList $Semantics)
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
				$Answer->getClause()->setAdverb($Adverb);
				$Answer->setSentenceType(Sentence::DECLARATIVE);
			}

		} elseif ($sentenceType == 'wh-question') {

			$answer = $this->answerQuestionWithSemantics($Semantics);
			if (!$answer) {
				$answer = $this->KnowledgeManager->answerQuestion($Sentence);
			}

			// incorporate the answer in the original question
			if ($answer !== false) {

				$Answer = $Sentence;

				#todo: this should be made more generic

				if ($Clause = $Answer->getClause()) {

					$found = false;

					// how many?
					if ($DeepDirectObject = $Clause->getDeepDirectObject()) {
						if ($Determiner = $DeepDirectObject->getDeterminer()) {
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
						if ($Preposition = $Clause->getPreposition()) {
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

	public function answerQuestionWithSemantics(PredicationList $PredicationList)
	{
		$InferenceEngine = new InferenceEngine();

		$knowledgeSources = array_merge(array(new PredicationListKnowledgeSource($PredicationList)), $this->KnowledgeManager->getKnowledgeSources());

		// extract the question predication
		$predications = $PredicationList->getPredications();
		if (!$predications) {
			return null;
		}

#todo: this is not the way, of course
		$Question = $predications[0];
		// turn request properties into variables
		$this->changeRequestPropertyInVariable($Question);

		$QuestionList = new PredicationList();
		$QuestionList->setPredications(array($Question));

		$bindings = $InferenceEngine->bind($QuestionList, $knowledgeSources, $this->KnowledgeManager->getRuleSources());

		// the variable 'request' in $bindings should hold the answer
		if ($bindings) {

			$firstBinding = reset($bindings);
			$response = $firstBinding['request'];

		} else {

			$response = null;

		}

		return $response;
	}

	private function changeRequestPropertyInVariable(Predication $Predication)
	{
		foreach ($Predication->getArguments() as $index => $Argument) {
			if ($Argument instanceof Property) {
				$name = $Argument->getName();
				if ($name == 'request') {
					$Variable = new Variable($name);
					$Predication->setArgument($index, $Variable);
				}
			}
		}
	}
}
