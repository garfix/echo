<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Atom;
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
		$recursionLevel = Utils::calculateRecursionLevel();

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
		if ($level >= self::MAX_RECURSION_LEVEL) {
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

			// knowledge sources may provide bindings
			$variableSets = array_merge($variableSets, $this->findFacts($knowledgeSources, $Predication, $variables));

			// rule sources may provide bindings
			$variableSets = array_merge($variableSets, $this->performRules($ruleSources, $Predication, $variables, $level + 1, $knowledgeSources));

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
	 * Tries to find variable sets for $Predication bound with $variables
	 * by executing goal clauses.
	 *
	 * @param $ruleSources
	 * @param $Predication
	 * @param $variables
	 * @param $level
	 * @param $knowledgeSources
	 * @return array
	 */
	private function performRules($ruleSources, $Predication, $variables, $level, $knowledgeSources)
	{
		$variableSets = array();

		foreach ($ruleSources as $RuleSource) {

			// ask for the rules that are applicable here
			$rules = $RuleSource->getRulesByPredicate($Predication->getPredicate(), $Predication->getArgumentCount());

			// go through each of these rules separately
			foreach ($rules as $GoalClause) {

				$variableSets = array_merge($variableSets, $this->performGoal($GoalClause, $Predication, $variables, $level + 1, $knowledgeSources, $ruleSources));

			}
		}
		return $variableSets;
	}

	/**
	 * Queries all $knowledgeSources for the $Predication that is bound with $variables.
	 *
	 * @param array $knowledgeSources
	 * @param Predication $Predication
	 * @param array $variables
	 * @return array
	 */
	private function findFacts(array $knowledgeSources, Predication $Predication, array $variables)
	{
#echo $Predication."\n";

		// create a set of predication variables
		$boundArguments = $this->bindPredicationArguments($Predication, $variables);
		if ($boundArguments === false) {
			// the predication contains properties and cannot be bound
			return array();
		}

		$variableSets = array();
		foreach ($knowledgeSources as $KnowledgeSource) {

			// ask this knowledge source for all rows that match the bound predication
			$resultSets = $KnowledgeSource->bind($Predication->getPredicate(), $boundArguments);

			// transform the index based results into variable assignments
			foreach ($resultSets as $resultSet) {
				$set = array();
				foreach ($resultSet as $index => $value) {
					$Argument = $Predication->getArgument($index);
					if ($Argument instanceof Variable) {
						$name = $Argument->getName();
						$set[$name] = $value;
					}
					// no need to match other objects than variables
				}
				$variableSets[] = $set;
			}
		}

		return $variableSets;
	}

	/**
	 * Creates an array that corresponds with the arguments of $Predication,
	 * with all values filled in.
	 *
	 * @param $Predication
	 * @param $variables
	 * @return array
	 */
	private function bindPredicationArguments($Predication, $variables)
	{
		$boundArguments = array();
		foreach ($Predication->getArguments() as $Argument) {
			if ($Argument instanceof Variable) {
				$name = $Argument->getName();
				$boundArguments[] = isset($variables[$name]) ? $variables[$name] : null;
			} elseif ($Argument instanceof Constant) {
				$boundArguments[] = $Argument->getName();
			} elseif ($Argument instanceof Atom) {
				$boundArguments[] = $Argument->getName();
			} else {
				return false;
			}
		}
		return $boundArguments;
	}

	/**
	 * Performs a subgoal in the predication list.
	 * One of the predications is matched against the goal of a goal clause and its means are used to create a new set of bindings
	 *
	 * @param GoalClause $GoalClause
	 * @param Predication $Predication
	 */
	private function performGoal(GoalClause $GoalClause, Predication $Predication, array $variables, $level, array $knowledgeSources, array $ruleSources)
	{
#echo $GoalClause."\n";

		$Goal = $GoalClause->getGoal()->getPredication(0);
		$Means = $GoalClause->getMeans();

		// map the variable bindings from the predication to the goal clause
		$goalVariables = array();
		$goal2predication = array();
		foreach ($Goal->getArguments() as $index => $Variable) {

			$variableName = $Variable->getName();

			// fetch the argument at the same position
			$Argument = $Predication->getArgument($index);

			if ($Argument instanceof Variable) {
				$argumentName = $Argument->getName();
				$goalVariables[$variableName] = isset($variables[$argumentName]) ? $variables[$argumentName] : null;
				$goal2predication[$argumentName] = $variableName;
			} elseif ($Argument instanceof Constant) {
				$goalVariables[$variableName] = $Argument->getName();
			} else {
				// leave atoms and properties unchanged
				$goalVariables[$variableName] = $Argument;
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
}
