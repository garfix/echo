<?php

namespace agentecho\component;

use agentecho\component\KnowledgeManager;
use agentecho\datastructure\ConversationContext;
use agentecho\exception\DataBaseMultipleResultsException;
use agentecho\exception\NoBindingsException;
use agentecho\phrasestructure\Sentence;
use agentecho\phrasestructure\Entity;
use agentecho\phrasestructure\Adverb;
use agentecho\phrasestructure\Date;
use agentecho\phrasestructure\SentenceBuilder;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Property;
use agentecho\datastructure\Variable;
use agentecho\component\Aggregator;
use agentecho\component\Assigner;
use agentecho\exception\MissingRequestFieldException;
use agentecho\exception\EchoException;

/**
 * This class answers question and processes imperatives.
 *
 * @author Patrick van Bergen
 */
class SentenceProcessor
{
	use EventSender;

	/** @var KnowledgeManager The agent having the conversation */
	private $KnowledgeManager;

	public function __construct(KnowledgeManager $KnowledgeManager)
	{
		$this->KnowledgeManager = $KnowledgeManager;
	}

	/**
	 * Returns a response to $question, using $Parser to parse the $question,
	 * and using $ConversationContext to process pronouns.
	 *
	 * @param string $question
	 * @param ConversationContext $ConversationContext
	 * @param Parser $Parser
	 * @return string
	 */
	public function reply($question, ConversationContext $ConversationContext, Parser $Parser)
	{
		$this->send(new LogEvent(array('question' => $question)));

		try {

			// parse the sentence
			$SentenceContext = $Parser->parseFirstLine($question);

			// update the current grammar from the language found in this sentence
			$CurrentGrammar = $Parser->getCurrentGrammar();

			// extract the Sentence
			$Sentence = $SentenceContext->getRootObject();
			$this->send(new LogEvent(array('syntax' => $SentenceContext->getPhraseSpecification())));
			$this->send(new LogEvent(array('phraseSpecification' => $Sentence)));

			// extract semantics
			$Semantics = $SentenceContext->getSemantics();

			// update the subject of the conversation
			$ContextProcessor = new ContextProcessor();
			$ContextProcessor->updateSubject($Sentence, $ConversationContext);

			// resolve pronouns
			$PronounProcessor = new PronounProcessor();
			$PronounProcessor->replacePronounsByProperNouns($Sentence, $ConversationContext);

			// replace references
			$PronounProcessor->replaceReferences($Semantics, $ConversationContext);
			$this->send(new LogEvent(array('semantics' => $Semantics)));

			// process the sentence
			$Response = $this->process($Sentence, $Semantics);
			$this->send(new LogEvent(array('response' => $Response)));

			// produce the surface text of the response
			$Producer = new Producer();
			$answer = $Producer->produce($Response, $CurrentGrammar);

			// substitute proper nouns by pronouns
#todo

		}
		catch (EchoException $E) {

			if ($E instanceof EchoException) {
				$translatedMessage = Translations::translate($E->getMessageText(), $Parser->getCurrentGrammar()->getLanguageCode());
				$E->setMessageText($translatedMessage);
			}

			$this->send(new LogEvent(array('backtrace' => $this->getFullTrace($E))));

			$answer = $E->getMessage();
		}

		return $answer;
	}

	private function getFullTrace(\Exception $E)
	{
		$topLevel = array(
			'file' => $E->getFile(),
			'line' => $E->getLine(),
			'class' => get_class($E),
			'function' => ''
		);

		return array_merge(array($topLevel), $E->getTrace());
	}

	/**
	 * @param Sentence $Sentence
	 * @return PhraseStructure
	 */
	private function process(Sentence $Sentence, PredicationList $Semantics)
	{
		$Answer = null;

		$sentenceType = $Sentence->getSentenceType();
		if ($sentenceType == 'yes-no-question') {

			// since this is a yes-no question, check the statement
			$result = $this->answerYesNoQuestionWithSemantics($Semantics);

			if ($result) {
				$Answer = $Sentence->createClone();

				$Adverb = new Adverb();
				$Adverb->setCategory('yes');
				$Answer->getClause()->setAdverb($Adverb);
				$Answer->setSentenceType(Sentence::DECLARATIVE);
			}

		} elseif ($sentenceType == 'wh-question') {

			$answer = $this->answerQuestionWithSemantics($Semantics);

			// incorporate the answer in the original question
			if ($answer !== null) {

				$answer = array_unique($answer);

				if (count($answer) > 1) {

					$Answer = $this->createConjunction($answer);

				} else {

					$answer = reset($answer);

					$Answer = $Sentence->createClone();

					#todo: this should be made more generic

					if ($Clause = $Answer->getClause()) {

						$found = false;

						// how many?
						if ($DeepDirectObject = $Clause->getDeepDirectObject()) {
							if ($Determiner = $DeepDirectObject->getDeterminer()) {
								if ($Determiner->isQuestion()) {
									$Answer->setSentenceType(Sentence::DECLARATIVE);
									$Determiner->setQuestion(false);

	#todo
	//$Determiner->setUnit($unit);

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
			}

		} elseif ($Sentence->getSentenceType() == 'imperative') {

			#todo Imperatives are not always questions
			$isQuestion = true;

			if ($isQuestion) {

				$answer = $this->answerQuestionWithSemantics($Semantics);

				if ($answer !== null) {

					$Answer = $this->createConjunction($answer);

				}

			}

		}

		return $Answer;
	}

	private function createConjunction($operands)
	{
		$entities = array();

		foreach ($operands as $name) {

                     $Entity = new Entity();
                     $Entity->setName($name);

                     $entities[] = $Entity;
		}
		return SentenceBuilder::buildConjunction($entities);
	}

	private function answerQuestionWithSemantics(PredicationList $PredicationList)
	{
		$Interpretation = $this->interpret($PredicationList);

		$bindings = $this->createBindings($Interpretation);

#todo: there should be only 1 result, or all results are identical

		// the variable 'request' in $bindings should hold the answer
		if ($bindings) {

			// find the first argument of the request-predication
			$Request = $Interpretation->getPredicationByPredicate('request');

			// check if there is a unit for the answer
			$Unit = $Interpretation->getPredicationByPredicate('unit');

			if ($Request) {
				$argument = $Request->getFirstArgument()->getName();

				$response = array();
				foreach ($bindings as $binding) {
					if (isset($binding[$argument])) {
						$response[] = $binding[$argument];
					} else {
						throw new MissingRequestFieldException();
					}
				}
			} else {
				throw new MissingRequestFieldException();
			}

		} else {

			throw new NoBindingsException();

		}

		return $response;
	}

	private function interpret(PredicationList $RawSemantics)
	{
		// extract the question predication
		$predications = $RawSemantics->getPredications();
		if (!$predications) {
			return array();
		}

		// replace all request properties with variables
		foreach ($predications as $Predication) {
			$this->changeRequestPropertyInVariable($Predication);
		}

		// first explode the predications into all possible solution paths
		// this is an array of predicationlists (or predication-arrays)
		$interpreters = $this->KnowledgeManager->getInterpreters();

		if (!empty($interpreters)) {

#todo: multiple
			$DataMapper = reset($interpreters);

			$DataMapper->setAllowUnprocessedPredications();
			$DataMapper->setIterate();

			$ExpandedQuestion = $DataMapper->mapPredications($RawSemantics);

		} else {

			$ExpandedQuestion = $RawSemantics;

		}

		$this->send(new LogEvent(array('interpretation' => $ExpandedQuestion)));

		return $ExpandedQuestion;
	}

	private function answerYesNoQuestionWithSemantics(PredicationList $PredicationList)
	{
		$Interpretation = $this->interpret($PredicationList);

		$a = (string)$Interpretation;

		$bindings = $this->createBindings($Interpretation);
		$this->send(new LogEvent(array('bindings' => $bindings)));

		if (count($bindings) > 1) {
			throw new DataBaseMultipleResultsException();
		} elseif (count($bindings) == 0) {
			throw new NoBindingsException();
		}

		return !empty($bindings);
	}

	private function createBindings(PredicationList $ExpandedQuestion)
	{
		$knowledgeSources = $this->KnowledgeManager->getKnowledgeSources();
		$bindings = array();
		$Exception = null;

		foreach ($knowledgeSources as $KnowledgeSource) {
$a = (string)$ExpandedQuestion;

			if (isset($this->EventManager)) {
				$KnowledgeSource->setEventManager($this->EventManager);
			}

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

		if (empty($bindings) && !is_null($Exception)) {
			throw $Exception;
		}

		$this->send(new LogEvent(array('bindings' => $bindings)));

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
