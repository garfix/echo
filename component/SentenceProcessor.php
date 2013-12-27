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
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Relation;
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
			foreach ($Semantics->getRelations() as $Relation) {
				$this->changeRequestPropertyInVariable($Relation);
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
	 * @param RelationList $Question
	 * @param Grammar $CurrentGrammar
	 * @return RelationList|false
	 */
	private function answer(RelationList $Question, Grammar $CurrentGrammar)
	{
		$Answer = false;

		$SentenceRelation = $Question->getRelationByPredicate('sentence');
		if (!$SentenceRelation) {
			return false;
		}

		/** @var Variable $SentenceEvent */
		$SentenceEvent = $SentenceRelation->getArgument(0);

		$MoodRelation = $Question->getRelationByPredicate('mood', [$SentenceEvent]);
		if (!$MoodRelation) {
			return false;
		}

		$mood = $MoodRelation->getArgument(1)->getName();
		if ($mood == 'Interrogative') {

			$Answer = $this->getInterrogativeAnswer($SentenceEvent, $Question, $CurrentGrammar);

		} elseif ($mood == 'Imperative') {

			$Answer =  $this->getImperativeAnswer($SentenceEvent, $Question);
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

	private function getInterrogativeAnswer(Variable $SentenceEvent, RelationList $Question, Grammar $CurrentGrammar)
	{
		// does the question contain a specific requested field?
		$RequestRelation = $Question->getRelationByPredicate('request');
		if ($RequestRelation) {

			$Answer = $this->getRequestedAnswer($SentenceEvent, $RequestRelation, $Question, $CurrentGrammar);

		} else {

			$Answer = $this->getYesNoAnswer($SentenceEvent, $Question);

		}

		return $Answer;
	}

	private function getRequestedAnswer(Variable $SentenceEvent, Relation $RequestRelation, RelationList $Question, Grammar $CurrentGrammar)
	{
		// since this is a yes-no question, check the statement
		list($answer, $unit) = $this->answerQuestionWithSemantics($Question);

		if (count($answer) > 1) {

			$Answer = $this->getConjunctiveAnswer($answer);

		} else {

			$answer = reset($answer);

			// generate answer from question
			$Answer = $Question->createClone();

			// replace mood
			$this->makeDeclarative($Answer, $SentenceEvent);

			// find requested object
			$RequestVariable = $RequestRelation->getArgument(0)->createClone();

			if ($MannerRelation = $Question->getRelationByPredicate('manner')) {
				if ($MannerRelation->getArgument(1) == $RequestVariable) {

					$M = RelationUtils::createUnusedVariable($Question->getVariableNames());
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
			} elseif ($LocationRelation = $Question->getRelationByPredicate('location')) {

				$E = $LocationRelation->getArgument(0);

				// create location
				$L = RelationUtils::createUnusedVariable($Question->getVariableNames());
				$this->addBinaryRelation($Answer, 'name', $L, new Constant($answer));

				// create link relation
				$this->addTertiaryRelation($Answer, 'link', new Atom('In'), $E, $L);

			} elseif ($LocationRelation = $Question->getRelationByPredicate('at_time')) {

				$E = $LocationRelation->getArgument(0);

				$Date = new \DateTime($answer);
				$languageCode = $CurrentGrammar->getLanguageCode();

				if ($languageCode == 'en') {
					setlocale(LC_TIME, 'en_US.UTF-8');
				} else {
					setlocale(LC_TIME, 'nl_NL');
				}

				if ($languageCode == 'en') {
					$date = strftime('%B %e, %Y', $Date->getTimestamp());
				} else {
					$date = strftime('%e %B %Y', $Date->getTimestamp());
				}

				// create time
				$L = RelationUtils::createUnusedVariable($Question->getVariableNames());
				$this->addBinaryRelation($Answer, 'name', $L, new Constant($date));

				// create link relation
				$this->addTertiaryRelation($Answer, 'link', new Atom('On'), $E, $L);

			} else {
				$Answer = false;
			}
		}

		return $Answer;
	}

	private function getYesNoAnswer(Variable $SentenceEvent, RelationList $Question)
	{
		$ComplementRelation = $Question->getRelationByPredicate('complement');
		if ($ComplementRelation) {

			$Answer = $this->getComplementYesNoAnswer($SentenceEvent, $ComplementRelation, $Question);

		} else {

			$Answer = $this->getDefaultYesNoAnswer($SentenceEvent, $Question);

		}

		return $Answer;
	}

	private function getComplementYesNoAnswer(Variable $SentenceEvent, Relation $ComplementRelation, RelationList $Question)
	{
		/** @var Variable $ComplementVariable */
		$ComplementVariable = $ComplementRelation->getArgument(1);

		$SubjectRelation = $Question->getRelationByPredicate('subject', [$SentenceEvent, null]);
		/** @var Variable $SubjectVariable */
		$SubjectVariable = $SubjectRelation->getArgument(1);

		// replace the complement variable with the subject variable
		// this creates a `union` of subject and complement. if this yields a match, the answer is yes
		$AdaptedQuestion = $Question->createClone();
		$this->replaceVariable($AdaptedQuestion, $ComplementVariable, $SubjectVariable);

		return $this->getDefaultYesNoAnswer($SentenceEvent, $AdaptedQuestion);
	}

	private function getDefaultYesNoAnswer(Variable $SentenceEvent, RelationList $Question)
	{
		$Answer = false;

		$result = $this->answerYesNoQuestionWithSemantics($Question);

		if ($result) {

			// generate answer from question
			$Answer = $Question->createClone();

			// replace mood
			$this->makeDeclarative($Answer, $SentenceEvent);

			// add 'yes'
			$Q = RelationUtils::createUnusedVariable($Question->getVariableNames());
			$this->addBinaryRelation($Answer, 'qualification', $SentenceEvent, $Q);
			$this->addBinaryRelation($Answer, 'isa', $Q, new Atom('Yes'));
		}

		return $Answer;
	}

	private function getImperativeAnswer(Variable $SentenceEvent, RelationList $Question)
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

		// sentence(?c0)
		$this->addSentenceRelation($Answer, $ConjunctionVariable);

		return $Answer;
	}

	private function replaceVariable(RelationList $Relations, Variable $V1, Variable $V2)
	{
		foreach ($Relations->getRelations() as $Relation) {
			foreach ($Relation->getArguments() as $i => $Argument) {
				if ($Argument == $V1) {
					$Relation->setArgument($i, $V2->createClone());
				}
			}
		}
	}

	private function addSentenceRelation(RelationList $Relations, Variable $Variable)
	{
		$SentenceRelation = new Relation();
		$SentenceRelation->setPredicate('sentence');

		$SentenceRelation->setArgument(0, $Variable);
		$Relations->addRelation($SentenceRelation);
	}

	private function makeDeclarative(RelationList $Relations, Variable $SentenceEvent)
	{
		$Mood = $Relations->getRelationByPredicate('mood');

		$Relations->removeRelation($Mood);
		$DeclarativeMood = new Relation();
		$DeclarativeMood->setPredicate('mood');
		$A0 = $SentenceEvent->createClone();
		$A1 = new Atom('Declarative');
		$DeclarativeMood->setArgument(0, $A0);
		$DeclarativeMood->setArgument(1, $A1);
		$Relations->addRelation($DeclarativeMood);
	}

	private function addBinaryRelation(RelationList $Relations, $predicate, $Arg0, $Arg1)
	{
		$Relation = new Relation();
		$Relation->setPredicate($predicate);
		$Relation->setArgument(0, $Arg0);
		$Relation->setArgument(1, $Arg1);
		$Relations->addRelation($Relation);
	}

	private function addTertiaryRelation(RelationList $Relations, $predicate, $Arg0, $Arg1, $Arg2)
	{
		$Relation = new Relation();
		$Relation->setPredicate($predicate);
		$Relation->setArgument(0, $Arg0);
		$Relation->setArgument(1, $Arg1);
		$Relation->setArgument(2, $Arg2);
		$Relations->addRelation($Relation);
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

	private function answerQuestionWithSemantics(RelationList $RelationList)
	{
		$Interpretation = $this->interpret($RelationList);

		$bindings = $this->createBindings($Interpretation);

		$unit = null;

		// the variable 'request' in $bindings should hold the answer
		if ($bindings) {

			// check if there is a unit for the answer
			$Unit = $Interpretation->getRelationByPredicate('request_unit');
			if ($Unit) {
				$unit = $Unit->getArgument(0);
			}

			// find the first argument of the request-relation
			$Request = $Interpretation->getRelationByPredicate('request');

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

		$response = array_unique($response);

		return array($response, $unit);
	}

	private function interpret(RelationList $RawSemantics)
	{
		$ExpandedQuestion = $RawSemantics;

		if ($this->KnowledgeManager) {

			// first explode the relations into all possible solution paths
			// this is an array of relationlists (or relation-arrays)
			$interpreters = $this->KnowledgeManager->getInterpreters();

			foreach($interpreters as $Interpreter) {

				$ExpandedQuestion = $Interpreter->mapRelations($RawSemantics, true, true);
			}
		}

		$this->send(new LogEvent(array('interpretation' => $ExpandedQuestion)));

		return $ExpandedQuestion;
	}

	private function answerYesNoQuestionWithSemantics(RelationList $RelationList)
	{
		$Interpretation = $this->interpret($RelationList);

		$bindings = $this->createBindings($Interpretation);
		$this->send(new LogEvent(array('bindings' => $bindings)));

		if (count($bindings) > 1) {
			throw new DataBaseMultipleResultsException();
		} elseif (count($bindings) == 0) {
			throw new NoBindingsException();
		}

		return !empty($bindings);
	}

	private function createBindings(RelationList $ExpandedQuestion)
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
	 * Invokes all `let`- and `aggregate`- relations in $Relations on $bindings
	 *
	 * @param $newBindings
	 * @param \agentecho\datastructure\RelationList $Relations
	 *
	 * @return array A new list of bindings.
	 */
	private function performTranslations($bindings, RelationList $Relations)
	{
		$Assigner = new Assigner();
		$Aggregator = new Aggregator();

		foreach ($bindings as &$binding) {
			foreach ($Relations->getRelations() as $Relation) {
				if ($Relation->getPredicate() == 'let') {
					$binding = $Assigner->applyLet($Relation, $binding);
				}
			}
		}

		foreach ($Relations->getRelations() as $Relation) {
			if ($Relation->getPredicate() == 'aggregate') {

				$bindings = array($Aggregator->applyAggregate($Relation, $bindings));
			}
		}

		return $bindings;
	}

	private function changeRequestPropertyInVariable(Relation $Relation)
	{
		foreach ($Relation->getArguments() as $index => $Argument) {
			if ($Argument instanceof Property) {
				$propertyName = $Argument->getName();
				$objectName = $Argument->getObject()->getName();
				$Variable = new Variable($objectName . '_' . $propertyName);
				$Relation->setArgument($index, $Variable);
			}
		}
	}
}
