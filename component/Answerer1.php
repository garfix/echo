<?php

namespace agentecho\component;

use agentecho\component\events\EventSender;
use agentecho\component\events\LogEvent;
use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
use agentecho\datastructure\SentenceInformation;
use agentecho\exception\BuildException;
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
 * @author Patrick van Bergen
 */
class Answerer1
{
	use EventSender;

	/**
	 * @param RelationList $Question
	 * @param Grammar $CurrentGrammar
	 * @param $KnowledgeManager
	 * @return RelationList|false
	 */
	public function answer(RelationList $Question, Grammar $CurrentGrammar, $KnowledgeManager)
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

			$Answer = $this->getInterrogativeAnswer($SentenceEvent, $Question, $CurrentGrammar, $KnowledgeManager);

		} elseif ($mood == 'Imperative') {

			$Answer =  $this->getImperativeAnswer($SentenceEvent, $Question, $KnowledgeManager);
		}

		return $Answer;
	}

	private function getInterrogativeAnswer(Variable $SentenceEvent, RelationList $Question, Grammar $CurrentGrammar, KnowledgeManager $KnowledgeManager)
	{
		// does the question contain a specific requested field?
		$RequestRelation = $Question->getRelationByPredicate('request');
		if ($RequestRelation) {

			$Answer = $this->getRequestedAnswer($SentenceEvent, $RequestRelation, $Question, $CurrentGrammar, $KnowledgeManager);

		} else {

			$Answer = $this->getYesNoAnswer($SentenceEvent, $Question, $KnowledgeManager);

		}

		return $Answer;
	}

	private function getRequestedAnswer(Variable $SentenceEvent, Relation $RequestRelation, RelationList $Question, Grammar $CurrentGrammar, KnowledgeManager $KnowledgeManager)
	{
		// since this is a yes-no question, check the statement
		list($answer, $unit) = $this->answerQuestionWithSemantics($Question, $KnowledgeManager);

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

	private function getYesNoAnswer(Variable $SentenceEvent, RelationList $Question, KnowledgeManager $KnowledgeManager)
	{
		$ComplementRelation = $Question->getRelationByPredicate('complement');
		if ($ComplementRelation) {

			$Answer = $this->getComplementYesNoAnswer($SentenceEvent, $ComplementRelation, $Question, $KnowledgeManager);

		} else {

			$Answer = $this->getDefaultYesNoAnswer($SentenceEvent, $Question, $KnowledgeManager);

		}

		return $Answer;
	}

	private function getComplementYesNoAnswer(Variable $SentenceEvent, Relation $ComplementRelation, RelationList $Question, KnowledgeManager $KnowledgeManager)
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

		return $this->getDefaultYesNoAnswer($SentenceEvent, $AdaptedQuestion, $KnowledgeManager);
	}

	private function getDefaultYesNoAnswer(Variable $SentenceEvent, RelationList $Question, KnowledgeManager $KnowledgeManager)
	{
		$Answer = false;

		$result = $this->answerYesNoQuestionWithSemantics($Question, $KnowledgeManager);

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

	private function getImperativeAnswer(Variable $SentenceEvent, RelationList $Question, KnowledgeManager $KnowledgeManager)
	{
		# presume this is a request for information

		list($answer, $unit) = $this->answerQuestionWithSemantics($Question, $KnowledgeManager);

		return $this->getConjunctiveAnswer($answer);
	}

	private function getConjunctiveAnswer($answer)
	{
		$conjuncts = array();
		foreach ($answer as $entry) {
			$conjuncts[] = new Constant($entry);
		}

		$ConjunctionVariable = new Variable('c0');

		$Answer = self::buildConjunction($conjuncts, $ConjunctionVariable);

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

	/**
	 * Creates a set of conjunction relations from given entities.
	 *
	 * @param array $entities Constants, atoms, or variables, or a combination thereof.
	 * @param \agentecho\datastructure\Variable $RootVariable
	 * @throws BuildException
	 * @return RelationList
	 */
	public static function buildConjunction(array $entities, Variable $RootVariable)
	{
		static $idGen = 0;

		if (count($entities) < 2) {
			throw new BuildException();
        }

		$Relations = new RelationList();
		$count = count($entities);

		$RightNode = new Variable('n' . ++$idGen);
		$NameNode = $entities[$count - 1];

		$Relation = new Relation();
		$Relation->setPredicate('name');
		$Relation->setArguments(array($RightNode, $NameNode));
		$Relations->addRelation($Relation);

		for ($i = $count - 2; $i >= 0; $i--) {

			if ($i == 0) {
				$TopNode = $RootVariable;
			} else {
				$TopNode = new Variable('n' . ++$idGen);
			}

			$LeftNode = new Variable('n' . ++$idGen);
			$NameNode = $entities[$i];

			$Relation = new Relation();
			$Relation->setPredicate('name');
			$Relation->setArguments(array($LeftNode, $NameNode));
			$Relations->addRelation($Relation);

			$Relation = new Relation();
			$Relation->setPredicate('link');
			$Relation->setArguments(array(new Atom('And'), $TopNode, $LeftNode, $RightNode));
			$Relations->addRelation($Relation);

			$RightNode = $TopNode;
		}

		return $Relations;
	}

	private function answerQuestionWithSemantics(RelationList $RelationList, KnowledgeManager $KnowledgeManager)
	{
		$Interpretation = $this->interpret($RelationList, $KnowledgeManager);

		$bindings = $this->createBindings($Interpretation, $KnowledgeManager);

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

	private function interpret(RelationList $RawSemantics, KnowledgeManager $KnowledgeManager)
	{
		$ExpandedQuestion = $RawSemantics;

		if ($KnowledgeManager) {

			// first explode the relations into all possible solution paths
			// this is an array of relationlists (or relation-arrays)
			$interpreters = $KnowledgeManager->getInterpreters();

			foreach($interpreters as $Interpreter) {

				$ExpandedQuestion = $Interpreter->mapRelations($RawSemantics, true, true);
			}
		}

		$this->send(new LogEvent(array('interpretation' => $ExpandedQuestion)));

		return $ExpandedQuestion;
	}

	private function answerYesNoQuestionWithSemantics(RelationList $RelationList, KnowledgeManager $KnowledgeManager)
	{
		$Interpretation = $this->interpret($RelationList, $KnowledgeManager);

		$bindings = $this->createBindings($Interpretation, $KnowledgeManager);
		$this->send(new LogEvent(array('bindings' => $bindings)));

		if (count($bindings) > 1) {
			throw new DataBaseMultipleResultsException();
		} elseif (count($bindings) == 0) {
			throw new NoBindingsException();
		}

		return !empty($bindings);
	}

	private function createBindings(RelationList $ExpandedQuestion, KnowledgeManager $KnowledgeManager)
	{
		$bindings = array();

		if ($KnowledgeManager) {

			$knowledgeSources = $KnowledgeManager->getKnowledgeSources();
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
	 * @param $bindings
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

}
