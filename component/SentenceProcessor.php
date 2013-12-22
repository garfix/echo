<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
use agentecho\datastructure\SentenceInformation;
use agentecho\exception\DataBaseMultipleResultsException;
use agentecho\exception\NoBindingsException;
use agentecho\exception\NoSemanticsAtTopLevelException;
use agentecho\exception\ParseException;
use agentecho\grammar\Grammar;
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

	public function __construct(KnowledgeManager $KnowledgeManager = null)
	{
		$this->KnowledgeManager = $KnowledgeManager;
	}

	/**
	 * Returns a response to $question, using $Parser to parse the $question,
	 * and using $ConversationContext to process pronouns.
	 *
	 * @param string $question
	 * @param Conversation $Conversation
	 * @return string
	 */
	public function reply($question, Conversation $Conversation)
	{
		// notify the question
		$this->send(new LogEvent(array('question' => $question)));

		try {

			// parse the sentence
			$SentenceInformation = $this->parseFirstLine($question, $Conversation);

			// update the current grammar from the language found in this sentence
			$CurrentGrammar = $Conversation->getCurrentGrammar();

			// notify the syntax
			$this->send(new LogEvent(array('syntax' => $SentenceInformation->getPhraseSpecification())));

			// get semantics
			$Semantics = $SentenceInformation->getSemantics();

			// replace references
			$PronounProcessor = new PronounProcessor();
			$PronounProcessor->replaceReferences($Semantics);

			// notify the semantics
			$this->send(new LogEvent(array('semantics' => $Semantics)));

			// replace all request properties with variables
			foreach ($Semantics->getPredications() as $Predication) {
				$this->changeRequestPropertyInVariable($Predication);
			}

			// answer the question
			$Answer = $this->answer($Semantics, $CurrentGrammar);

			// substitute proper nouns by pronouns
#todo

			// generate the surface representation
			$Generator = new Generator();
			$answer = $Generator->generate($CurrentGrammar, $Answer);

		} catch (EchoException $E) {

			if ($E instanceof EchoException) {
				$translatedMessage = Translations::translate($E->getMessageText(), $Conversation->getCurrentGrammar()->getLanguageCode());
				$E->setMessageText($translatedMessage);
			}

			$this->send(new LogEvent(array('backtrace' => $this->getFullTrace($E))));

			$answer = $E->getMessage();
		}

		return $answer;
	}

	/**
	 * @param PredicationList $Question
	 * @param Grammar $CurrentGrammar
	 * @return PredicationList|false
	 */
	private function answer(PredicationList $Question, Grammar $CurrentGrammar)
	{
		$Answer = false;

		$MoodRelation = $Question->getPredicationByPredicate('mood');
		if (!$MoodRelation) {
			return false;
		}

		$mood = $MoodRelation->getArgument(1)->getName();
		if ($mood == 'Interrogative') {

			$Answer = $this->getInterrogativeAnswer($MoodRelation, $Question, $CurrentGrammar);

		} elseif ($mood == 'Imperative') {

			$Answer =  $this->getImperativeAnswer($MoodRelation, $Question);
		}

		return $Answer;
	}

	/**
	 * Tries to parse $input in the $Conversation's current grammar,
	 * or any of its other grammars.
	 *
	 * @param $input
	 * @param Conversation $Conversation
	 * @return array
	 * @throws \Exception
	 * @throws null
	 */
	public function parse($input, Conversation $Conversation)
	{
		$this->send(new Event($input));

		if (trim($input) == '') {
			return [];
		}

		$sentences = array();

		// create an array of grammars in which the current one is in the front
		$grammars = array($Conversation->getCurrentGrammar());

		foreach ($Conversation->getGrammars() as $Grammar) {
			if ($Grammar != $Conversation->getCurrentGrammar()) {
				$grammars[] = $Grammar;
			}
		}

		// keep track of the _first_ exception that is cast
		$Exception = null;

		// try to parse the sentence in each of the available grammars
		foreach ($grammars as $Grammar) {

			try {

				// parse a single line with a single grammar
				$Sentence = $this->parseSentence($input, $Grammar);

				$sentences[] = $Sentence;

				// update current language
				$Conversation->setCurrentGrammar($Grammar);

				// now parse the rest of the input, if there is one
				$restInput = str_replace($Sentence->getSurfaceText(), '', $input);

				return array_merge($sentences, $this->parse($restInput, $Conversation));

			} catch (\Exception $E) {

				// save the first exception
				if (!$Exception) {
					$Exception = $E;
				}

			}
		}

		// all grammars failed; throw the first exception
		throw $Exception;
	}

	/**
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @param Conversation $Conversation
	 * @return SentenceInformation
	 */
	public function parseFirstLine($input, Conversation $Conversation)
	{
		$sentences = $this->parse($input, $Conversation);
		return $sentences ? $sentences[0] : false;
	}

	/**
	 * This function turns a line of text into a SentenceInformation
	 *
	 * @param $input
	 * @param Grammar $Grammar
	 * @return \agentecho\datastructure\SentenceInformation
	 * @throws \agentecho\exception\ParseException
	 * @throws \agentecho\exception\NoSemanticsAtTopLevelException
	 */
	public function parseSentence($input, Grammar $Grammar)
	{
		$Sentence = new SentenceInformation();

		$Sentence->setLanguage($Grammar->getLanguage());

		// analyze words
		$Lexer = new Lexer();
		$Lexer->analyze($input, $Sentence, $Grammar);

		// create a phrase specification from these lexical items
		$result = EarleyParser::getFirstTree($Grammar, $Sentence->getLexicalItems());
		$Sentence->setPhraseSpecification($result['tree']);
		$Sentence->setSemantics($result['tree']['semantics']);

		if (!$result['success']) {
			throw new ParseException(implode(' ', array_slice($Sentence->getLexicalItems(), $result['lastParsedIndex'] - 1, 4)));
		}

		if ($result['tree']['semantics'] === null) {
			throw new NoSemanticsAtTopLevelException();
		}

		return $Sentence;
	}

	private function getInterrogativeAnswer(Predication $MoodRelation, PredicationList $Question, Grammar $CurrentGrammar)
	{
		// does the question contain a specific requested field?
		$RequestRelation = $Question->getPredicationByPredicate('request');
		if ($RequestRelation) {

			$Answer = $this->getRequestedAnswer($MoodRelation, $RequestRelation, $Question, $CurrentGrammar);

		} else {

			$Answer = $this->getYesNoAnswer($MoodRelation, $Question);

		}

		return $Answer;
	}

	private function getRequestedAnswer(Predication $MoodRelation, Predication $RequestRelation, PredicationList $Question, Grammar $CurrentGrammar)
	{
		// since this is a yes-no question, check the statement
		list($answer, $unit) = $this->answerQuestionWithSemantics($Question);

		/** @var Variable $EventVariable */
		$EventVariable = $MoodRelation->getArgument(0)->createClone();

		# todo: remove earlier?
		$answer = array_unique($answer);

		if (count($answer) > 1) {

			$Answer = $this->getConjunctiveAnswer($answer);

		} else {

			$answer = reset($answer);

			// generate answer from question
			$Answer = $Question->createClone();

			// sentence(?e, S.event)
			$this->addSentenceRelation($Answer, $EventVariable);

			// replace mood
			$this->makeDeclarative($Answer, $EventVariable);

			// find requested object
			$RequestVariable = $RequestRelation->getArgument(0)->createClone();

			if ($MannerRelation = $Question->getPredicationByPredicate('manner')) {
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
				}
			} elseif ($LocationRelation = $Question->getPredicationByPredicate('location')) {

				$E = $LocationRelation->getArgument(0);

				// create location
				$L = new Variable('v1'); # todo: create new variable
				$this->addBinaryRelation($Answer, 'name', $L, new Constant($answer));

				// create link relation
				$this->addTertiaryRelation($Answer, 'link', new Atom('In'), $E, $L);

			} elseif ($LocationRelation = $Question->getPredicationByPredicate('at_time')) {

				$E = $LocationRelation->getArgument(0);

				$Date = new \DateTime($answer);
				$languageCode = $CurrentGrammar->getLanguageCode();

				setlocale(LC_TIME, $languageCode . '_' . strtoupper($languageCode));

				if ($languageCode == 'en') {
	#todo why needed?
					$date = ucfirst(strftime('%B %e, %Y', $Date->getTimestamp()));
				} else {
					$date = strftime('%e %B %Y', $Date->getTimestamp());
				}

				// create time
				$L = new Variable('v1'); # todo: create new variable
				$this->addBinaryRelation($Answer, 'name', $L, new Constant($date));

				// create link relation
				$this->addTertiaryRelation($Answer, 'link', new Atom('On'), $E, $L);

			} else {
				$Answer = false;
			}
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

		return $this->getConjunctiveAnswer($answer);
	}

	private function getConjunctiveAnswer($answer)
	{
		$conjuncts = array();
		foreach ($answer as $entry) {
			$conjuncts[] = new Constant($entry);
		}

		$ConjunctionVariable = new Variable('c0');

		$Answer = SentenceBuilder::buildConjunction($conjuncts, $ConjunctionVariable);

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

	private function addTertiaryRelation(PredicationList $Relations, $predicate, $Arg0, $Arg1, $Arg2)
	{
		$Relation = new Predication();
		$Relation->setPredicate($predicate);
		$Relation->setArgument(0, $Arg0);
		$Relation->setArgument(1, $Arg1);
		$Relation->setArgument(2, $Arg2);
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
		$ExpandedQuestion = $RawSemantics;

		if ($this->KnowledgeManager) {

			// first explode the predications into all possible solution paths
			// this is an array of predicationlists (or predication-arrays)
			$interpreters = $this->KnowledgeManager->getInterpreters();

			if (!empty($interpreters)) {

	#todo: multiple
				$DataMapper = reset($interpreters);

				$DataMapper->setAllowUnprocessedPredications();
				$DataMapper->setIterate();

				$ExpandedQuestion = $DataMapper->mapPredications($RawSemantics);

			}

		}

		$this->send(new LogEvent(array('interpretation' => $ExpandedQuestion)));

		return $ExpandedQuestion;
	}

	private function answerYesNoQuestionWithSemantics(PredicationList $PredicationList)
	{
		$Interpretation = $this->interpret($PredicationList);

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
		$bindings = array();

		if ($this->KnowledgeManager) {

			$knowledgeSources = $this->KnowledgeManager->getKnowledgeSources();
			$Exception = null;

			foreach ($knowledgeSources as $KnowledgeSource) {

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
