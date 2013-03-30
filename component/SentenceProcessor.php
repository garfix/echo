<?php

namespace agentecho\component;

use agentecho\component\KnowledgeManager;
use agentecho\phrasestructure\Sentence;
use agentecho\phrasestructure\Entity;
use agentecho\phrasestructure\Adverb;
use agentecho\phrasestructure\Date;
use agentecho\phrasestructure\SentenceBuilder;
use agentecho\datastructure\PredicationList;
use agentecho\component\InferenceEngine;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Property;
use agentecho\datastructure\Variable;
use agentecho\exception\ParseException;
use agentecho\component\Aggregator;
use agentecho\component\Assigner;
use agentecho\exception\MissingRequestFieldException;

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
		global $NEW;

		$Answer = null;

		$sentenceType = $Sentence->getSentenceType();
		if ($sentenceType == 'yes-no-question') {

			// since this is a yes-no question, check the statement


if ($NEW) {
	$result = $this->answerYesNoQuestionWithSemantics($Semantics);
} else {
			$result = $this->KnowledgeManager->checkQuestion($Sentence);
}

			if ($result) {
				$Answer = $Sentence;

				$Adverb = new Adverb();
				$Adverb->setCategory('yes');
				$Answer->getClause()->setAdverb($Adverb);
				$Answer->setSentenceType(Sentence::DECLARATIVE);
			}

		} elseif ($sentenceType == 'wh-question') {

			if ($NEW) {
				$answer = $this->answerQuestionWithSemantics($Semantics);
			} else {
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

				if ($NEW) {
					$answer = $this->answerQuestionWithSemantics($Semantics);
				} else {
					$answer = $this->KnowledgeManager->answerQuestion($Sentence);
				}

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
		$bindings = $this->createBindings($PredicationList);

#todo: there should be only 1 result, or all results are identical

		// the variable 'request' in $bindings should hold the answer
		if ($bindings) {

			$firstBinding = reset($bindings);
			if (isset($firstBinding['request'])) {
				$response = $firstBinding['request'];
			} elseif (isset($firstBinding['S_request'])) {
				$response = $firstBinding['S_request'];
			} else {
				throw new MissingRequestFieldException();
			}

		} else {

			$response = null;

		}

		return $response;
	}

	private function answerYesNoQuestionWithSemantics(PredicationList $PredicationList)
	{
		$bindings = $this->createBindings($PredicationList);

		if (count($bindings) > 1) {
			throw new ParseException(ParseException::DB_MORE_THAN_ONE_RESULT);
		}

		return !empty($bindings);
	}

	private function createBindings(PredicationList $PredicationList)
	{
		$knowledgeSources = $this->KnowledgeManager->getKnowledgeSources();

		// extract the question predication
		$predications = $PredicationList->getPredications();
		if (!$predications) {
			return array();
		}

		// replace all request properties with variables
		foreach ($predications as $Predication) {
			$this->changeRequestPropertyInVariable($Predication);
		}

		// first explode the predications into all possible solution paths
		// this is an array of predicationlists (or predication-arrays)
		$ruleSources = $this->KnowledgeManager->getElaborators();

#todo: multiple
$ruleSource = reset($ruleSources);

		$DataMapper = $ruleSource;
		$DataMapper->setAllowUnprocessedPredications();
		$DataMapper->setIterate();

		$ExpandedQuestion = $DataMapper->mapPredications($PredicationList);

		$bindings = array();

		$Exception = null;

		#foreach ($expandedQuestions as $ExpandedQuestion) {

			foreach ($knowledgeSources as $KnowledgeSource) {
$a = (string)$ExpandedQuestion;

				try {
					// execute the query
					$newBindings = $KnowledgeSource->answer($ExpandedQuestion);

					if ($newBindings) {

						// perform the translations
						$newBindings = $this->performTranslations($newBindings, $ExpandedQuestion);

						$bindings = array_merge($bindings, $newBindings);
					}

				} catch (\Exception $E) {
					$Exception = $E;
				}

			}
		#}

		if (empty($bindings) && !is_null($Exception)) {
			throw $Exception;
		}

		return $bindings;
	}

	/**
	 * Invokes all `let`- and `aggregate`- predications in $Predications on $bindings
	 *
	 * @param $newBindings
	 * @param \agentecho\datastructure\PredicationList $Predications
	 *
	 * @return array A new list of bindings.
	 */
	private function performTranslations($bindings, PredicationList $Predications)
	{
		$Assigner = new Assigner();
		$Aggregator = new Aggregator();

		foreach ($bindings as &$binding) {
			foreach ($Predications->getPredications() as $Predication) {
				if ($Predication->getPredicate() == 'let') {
					$binding = $Assigner->applyLet($Predication, $binding);
				}
			}
		}

		foreach ($Predications->getPredications() as $Predication) {
			if ($Predication->getPredicate() == 'aggregate') {

				$bindings = array($Aggregator->applyAggregate($Predication, $bindings));
			}
		}

		return $bindings;
	}

	private function changeRequestPropertyInVariable(Predication $Predication)
	{
		foreach ($Predication->getArguments() as $index => $Argument) {
			if ($Argument instanceof Property) {
				$propertyName = $Argument->getName();
				$objectName = $Argument->getObject()->getName();
				$Variable = new Variable($objectName . '_' . $propertyName);
				$Predication->setArgument($index, $Variable);
			}
		}
	}
}
