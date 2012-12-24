<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Constant;
use agentecho\datastructure\GoalClause;
use agentecho\exception\RecursionException;
use agentecho\component\Utils;

/**
 * This class provides a Prolog-like type of creating sets of predication variables.
 *
 * @author Patrick van Bergen
 */
class InferenceEngine
{
	/** Max recursion level in PHP is 100; we need some extra room for functions that are not monitored */
	const MAX_RECURSION_LEVEL = 90;

	/**
	 * This function processes a series of predications and returns a list of all variable binding-sets
	 * that apply to the predications.
	 *
	 * The binding is performed on each of the knowledge sources and rule sources provided.
	 *
	 * @param PredicationList $PredicationList
	 * @param array $knowledgeSources
	 * @param array $ruleSources
	 *
	 * @return array
	 */
	public function bind(PredicationList $PredicationList, array $knowledgeSources, array $ruleSources)
	{
		// PHP allows a recursion depth of only 100; we take this into account here
		$recursionLevel = Utils::calculcateRecursionLevel();

		// bind the variables in the predication list to actual values
		$variableSets = $this->bindPredicationTail($PredicationList->getPredications(), array(), $recursionLevel, $knowledgeSources, $ruleSources);

		return $variableSets;
	}

	/**
	 * Recursive function to process the rest of the predications.
	 *
	 * @param array $predications
	 * @param array $variables
	 * @param array $knowledgeSources
	 * @param array $ruleSources
	 * @return array
	 */
	private function bindPredicationTail(array $predications, array $variables, $level, array $knowledgeSources, array $ruleSources)
	{
		if ($level == self::MAX_RECURSION_LEVEL) {
			throw new RecursionException();
		}

		if (empty($predications)) {

			// no predications => unification succeeds, keep the variables
			return array($variables);

		} else {

			/** @var array $variableSets An array of sets of variable bindings. (i.e. [ [a = 2, b = 5], [a = 4, b = 7] ] */
			$variableSets = array();

			// take the first predication
			$Predication = array_shift($predications);

			// fill the variables of $Predication with the current bindings
			$BoundPredication = $this->bindPredication($Predication, $variables);

			// knowledge sources may provide bindings
			foreach ($knowledgeSources as $KnowledgeSource) {

				$variableSets = array_merge($variableSets, $KnowledgeSource->bind($BoundPredication));

			}

			// rule sources may provide bindings
			foreach ($ruleSources as $RuleSource) {

				// ask for the rules that are applicable here
				$rules = $RuleSource->getRulesByPredicate($Predication->getPredicate(), $Predication->getArgumentCount());

				// go through each of these rules separately
				foreach ($rules as $GoalClause) {

					$variableSets = array_merge($variableSets, $this->performGoal($GoalClause, $BoundPredication, $level + 1, $knowledgeSources, $ruleSources));

				}
			}

			// for each of these newly gathered variable bindings, continue with the rest of the predications
			$variableSetsTail = array();
			foreach ($variableSets as $newVariables) {

				// the rule - and knowledge sources may not have returned the initial variables, so we combine them here
				$combinedVariables = array_merge($variables, $newVariables);

				// unify the variables with the rest of the predications
				$variableSetsTail = array_merge($variableSetsTail, $this->bindPredicationTail($predications, $combinedVariables, $level + 1, $knowledgeSources, $ruleSources));

			}

			return $variableSetsTail;
		}
	}

	/**
	 * Performs a subgoal in the predication list.
	 * One of the predications is matched against the goal of a goal clause and its means are used to create a new set of bindings
	 *
	 * @param GoalClause $GoalClause
	 * @param Predication $Predication
	 */
	private function performGoal(GoalClause $GoalClause, Predication $Predication, $level, array $knowledgeSources, array $ruleSources)
	{
		$Goal = $GoalClause->getGoal();
		$Means = $GoalClause->getMeans();

		if ($level == self::MAX_RECURSION_LEVEL) {
			throw new RecursionException();
		}

		// map the variable bindings from the predication to the goal close
		$goalVariables = array();
		$goal2predication = array();
		foreach ($Goal->getArguments() as $index => $Variable) {
			$name = $Variable->getName();

			$Argument = $Predication->getArgument($index);
			if ($Argument instanceof Variable) {
				$goalVariables[$name] = $Argument->getValue();
				$goal2predication[$Argument->getName()] = $name;
			} elseif ($Argument instanceof Constant) {
				$goalVariables[$name] = $Argument->getName();
			}
		}

		// process the means of the goal close with the variables
		$goalSets = $this->bindPredicationTail($Means->getPredications(), $goalVariables, $level + 1, $knowledgeSources, $ruleSources);

		// map the variable bindings from the goal clause back to the calling clause
		$variableSets = array();
		foreach ($goalSets as $goalSet) {
			$set = array();
			foreach ($goal2predication as $predicationVariable => $goalVariable) {
				$set[$predicationVariable] = $goalSet[$goalVariable];
			}
			$variableSets[] = $set;
		}

		return $variableSets;
	}

	private function bindPredication(Predication $Predication, $bindings)
	{
		$BoundPredication = $Predication->createClone();

		foreach ($BoundPredication->getArguments() as $Argument) {
			if ($Argument instanceof Variable) {
				$name = $Argument->getName();

				if (isset($bindings[$name])) {
					$value = $bindings[$name];
					$Argument->setValue($value);
				}
			}
		}

		return $BoundPredication;
	}
}
