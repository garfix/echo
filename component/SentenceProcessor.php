<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
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

			// replace all request properties with variables
			foreach ($Semantics->getPredications() as $Predication) {
				$this->changeRequestPropertyInVariable($Predication);
			}

			$Answer = $this->answer($Semantics);
			if ($Answer) {

				$Generator = new Generator();
				$answer = $Generator->generate($CurrentGrammar, $Answer);

			} else {

				// process the sentence
				$Response = $this->process($Sentence, $Semantics);
				$this->send(new LogEvent(array('response' => $Response)));

				// produce the surface text of the response
				$Producer = new Producer();
				$answer = $Producer->produce($Response, $CurrentGrammar);

			}

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

	/**
	 * @param PredicationList $Question
	 * @return PredicationList|false
	 */
	private function answer(PredicationList $Question)
	{
		$Answer = false;
//return false;
		$MoodRelation = $Question->getPredicationByPredicate('mood');
		if (!$MoodRelation) {
			return false;
		}

		$mood = $MoodRelation->getArgument(1)->getName();
		if ($mood == 'Interrogative') {

			$Answer = $this->getInterrogativeAnswer($MoodRelation, $Question);

		} elseif ($mood == 'Imperative') {

			$Answer =  $this->getImperativeAnswer($MoodRelation, $Question);
		}

		return $Answer;
	}

	private function getInterrogativeAnswer(Predication $MoodRelation, PredicationList $Question)
	{
		// does the question contain a specific requested field?
		$RequestRelation = $Question->getPredicationByPredicate('request');
		if ($RequestRelation) {

			$Answer = $this->getRequestedAnswer($MoodRelation, $RequestRelation, $Question);

		} else {

			$Answer = $this->getYesNoAnswer($MoodRelation, $Question);

		}

		return $Answer;
	}

	private function getRequestedAnswer(Predication $MoodRelation, Predication $RequestRelation, PredicationList $Question)
	{
		// since this is a yes-no question, check the statement
		list($answer, $unit) = $this->answerQuestionWithSemantics($Question);

		/** @var Variable $EventVariable */
		$EventVariable = $MoodRelation->getArgument(0)->createClone();

		#todo: refine
		$answer = reset($answer);

		// generate answer from question
		$Answer = $Question->createClone();

		// sentence(?e, S.event)
		$this->addSentenceRelation($Answer, $EventVariable);

		// replace mood
		$this->makeDeclarative($Answer, $EventVariable);

		// find requested object
		$RequestVariable = $RequestRelation->getArgument(0)->createClone();

		$MannerRelation = $Question->getPredicationByPredicate('manner');
		if ($MannerRelation) {
			if ($MannerRelation->getArgument(1) == $RequestVariable) {

				$M = new Variable('v1'); # todo: create new variable
				$R = $MannerRelation->getArgument(0);

				// append the answer
				if ($unit) {
					$this->addBinaryRelation($Answer, 'modifier', $R, $M);
					$this->addBinaryRelation($Answer, 'determiner', $M, new Atom($answer));
					$this->addBinaryRelation($Answer, 'isa', $M, $unit->createClone());
				} else {
					$this->addBinaryRelation($Answer, 'determiner', $R, new Atom($answer));
				}
//echo $Answer;exit;
			}
		} else {
			$Answer = false;
		}

		return $Answer;
	}

	private function getYesNoAnswer(Predication $MoodRelation, PredicationList $Question)
	{
		$ComplementRelation = $Question->getPredicationByPredicate('complement');
		if ($ComplementRelation) {

			$Answer = $this->getComplementYesNoAnswer($MoodRelation, $ComplementRelation, $Question);

		} else {

			$Answer = $this->getDefaultYesNoAnswer($MoodRelation, $Question);

		}

		return $Answer;
	}

	private function getComplementYesNoAnswer(Predication $MoodRelation, Predication $ComplementRelation, PredicationList $Question)
	{
		/** @var Variable $EventVariable */
		$EventVariable = $ComplementRelation->getArgument(0);

		/** @var Variable $ComplementVariable */
		$ComplementVariable = $ComplementRelation->getArgument(1);

		$SubjectRelation = $Question->getPredicationByPredicate('subject', [$EventVariable, null]);
		/** @var Variable $SubjectVariable */
		$SubjectVariable = $SubjectRelation->getArgument(1);

		// replace the complement variable with the subject variable
		// this creates a `union` of subject and complement. if this yields a match, the answer is yes
		$AdaptedQuestion = $Question->createClone();
		$this->replaceVariable($AdaptedQuestion, $ComplementVariable, $SubjectVariable);

		return $this->getDefaultYesNoAnswer($MoodRelation, $AdaptedQuestion);
	}

	private function getDefaultYesNoAnswer(Predication $MoodRelation, PredicationList $Question)
	{
		$Answer = false;

		$result = $this->answerYesNoQuestionWithSemantics($Question);

		if ($result) {

			// generate answer from question
			$Answer = $Question->createClone();

			/** @var Variable $EventVariable */
			$EventVariable = $MoodRelation->getArgument(0)->createClone();

			// sentence(?e, S.event)
			$this->addSentenceRelation($Answer, $EventVariable);

			// replace mood
			$this->makeDeclarative($Answer, $EventVariable);

			// add 'yes'
			$Q = new Variable('v1'); # todo: create new variable
			$this->addBinaryRelation($Answer, 'qualification', $EventVariable, $Q);
			$this->addBinaryRelation($Answer, 'isa', $Q, new Atom('Yes'));
		}

		return $Answer;
	}

	private function getImperativeAnswer(Predication $MoodRelation, PredicationList $Question)
	{
		# presume this is a request for information

		list($answer, $unit) = $this->answerQuestionWithSemantics($Question);

		$conjuncts = array();
		foreach ($answer as $entry) {
			$conjuncts[] = new Constant($entry);
		}

		$ConjunctionVariable = new Variable('c0');

		$Answer = SentenceBuilder::buildConjunction2($conjuncts, $ConjunctionVariable);

		// sentence(?c0, CP.node)

		$ConjunctionProperty = new Property();
		$ConjunctionProperty->setName('node');
		$ConjunctionProperty->setObject(new Atom('CP'));
		$this->addSentenceRelation($Answer, $ConjunctionVariable, $ConjunctionProperty);

		return $Answer;
	}

	private function replaceVariable(PredicationList $Relations, Variable $V1, Variable $V2)
	{
		foreach ($Relations->getPredications() as $Predication) {
			foreach ($Predication->getArguments() as $i => $Argument) {
				if ($Argument == $V1) {
					$Predication->setArgument($i, $V2->createClone());
				}
			}
		}
	}

	private function addSentenceRelation(PredicationList $Relations, Variable $Variable, Property $Property = null)
	{
		$SentenceRelation = new Predication();
		$SentenceRelation->setPredicate('sentence');
		$A0 = $Variable->createClone();

		if (!$Property) {
			$A1 = new Property();
			$A1->setObject(new Atom('S'));
			$A1->setName('event');
		} else {
			$A1 = $Property;
		}
		$SentenceRelation->setArgument(0, $A0);
		$SentenceRelation->setArgument(1, $A1);
		$Relations->addPredication($SentenceRelation);
	}

	private function makeDeclarative(PredicationList $Relations, Variable $EventVariable)
	{
		$Mood = $Relations->getPredicationByPredicate('mood');

		$Relations->removePredication($Mood);
		$DeclarativeMood = new Predication();
		$DeclarativeMood->setPredicate('mood');
		$A0 = $EventVariable->createClone();
		$A1 = new Atom('Declarative');
		$DeclarativeMood->setArgument(0, $A0);
		$DeclarativeMood->setArgument(1, $A1);
		$Relations->addPredication($DeclarativeMood);
	}

	private function addBinaryRelation(PredicationList $Relations, $predicate, $Arg0, $Arg1)
	{
		$Relation = new Predication();
		$Relation->setPredicate($predicate);
		$Relation->setArgument(0, $Arg0);
		$Relation->setArgument(1, $Arg1);
		$Relations->addPredication($Relation);
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

			list($answer, $unit) = $this->answerQuestionWithSemantics($Semantics);

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

									if ($unit) {
										$Determiner->setUnit($unit);
									}

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

				list($answer, $unit) = $this->answerQuestionWithSemantics($Semantics);
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

		$unit = null;

#todo: there should be only 1 result, or all results are identical

		// the variable 'request' in $bindings should hold the answer
		if ($bindings) {

			// check if there is a unit for the answer
			$Unit = $Interpretation->getPredicationByPredicate('request_unit');
			if ($Unit) {
				$unit = $Unit->getArgument(0);
			}

			// find the first argument of the request-predication
			$Request = $Interpretation->getPredicationByPredicate('request');

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

		return array($response, $unit);
	}

	private function interpret(PredicationList $RawSemantics)
	{
//		// extract the question predication
//		$predications = $RawSemantics->getPredications();
//		if (!$predications) {
//			return array();
//		}
//
//		// replace all request properties with variables
//		foreach ($predications as $Predication) {
//			$this->changeRequestPropertyInVariable($Predication);
//		}

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
