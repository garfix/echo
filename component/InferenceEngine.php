<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\datastructure\GoalClause;

/**
 * This class provides a Prolog-like type of creating sets of predication variables.
 *
 * @author Patrick van Bergen
 */
class InferenceEngine
{
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
		// bind the variables in the predication list to actual values
		$variableSets = $this->bindPredicationTail($PredicationList->getPredications(), array(), $knowledgeSources, $ruleSources);

		// remove the temporary variables used in the process
		$variableSets = $this->removeHelperVariables($variableSets, $PredicationList);

		return $variableSets;
	}

	/**
	 * Removes all variables of $variableSets that are not used in $PredicationList
	 *
	 * @param array $variableSets
	 * @param PredicationList $PredicationList
	 * @return array
	 */
	private function removeHelperVariables(array $variableSets, PredicationList $PredicationList)
	{
		// collect variables
		$variables = $PredicationList->getVariableNames();

		// remove variables other than the ones used in the predication list
		$newSets = array();
		foreach ($variableSets as $set) {
			$newSets[] = array_intersect_key($set, $variables);
		}

		return $newSets;
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
	private function bindPredicationTail(array $predications, array $variables, array $knowledgeSources, array $ruleSources)
	{
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

					$variableSets = array_merge($variableSets, $this->performGoal($GoalClause, $BoundPredication, $knowledgeSources, $ruleSources));

				}
			}

			// for each of these newly gathered variable bindings, continue with the rest of the predications
			$variableSetsTail = array();
			foreach ($variableSets as $newVariables) {

				// the rule - and knowledge sources may not have returned the initial variables, so we combine them here
				$combinedVariables = array_merge($variables, $newVariables);

				// unify the variables with the rest of the predications
				$variableSetsTail = array_merge($variableSetsTail, $this->bindPredicationTail($predications, $combinedVariables, $knowledgeSources, $ruleSources));

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
	private function performGoal(GoalClause $GoalClause, Predication $Predication, array $knowledgeSources, array $ruleSources)
	{
		$Goal = $GoalClause->getGoal();
		$Means = $GoalClause->getMeans();

		// bind the goal variables to the predication arguments
		$variables = array();
		foreach ($Goal->getArguments() as $index => $Variable) {
			$name = $Variable->getName();
			$variables[$name] = $Predication->getArgument($index)->getValue();
		}

		$variableSets = $this->bindPredicationTail($Means->getPredications(), $variables, $knowledgeSources, $ruleSources);

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
