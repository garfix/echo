<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\datastructure\GoalClause;

/**
 * This class provides a Prolog-like type of binding predication variables.
 *
 * @author Patrick van Bergen
 */
class InferenceEngine
{
	/**
	 * This function processes a series of predications and returns a list of all binding-sets
	 * that apply to the predications.
	 *
	 * The actual binding is performed on each of the knowledge and rule sources provided.
	 *
	 * @param PredicationList $PredicationList
	 * @param array $knowledgeSources
	 * @param array $ruleSources
	 *
	 * @return array
	 */
	public function bind(PredicationList $PredicationList, array $knowledgeSources, array $ruleSources)
	{
		$bindings = $this->bindPredicationTail($PredicationList->getPredications(), array(), $knowledgeSources, $ruleSources);
#todo find a way to ensure that all objects are cloned at the right time

//		$variableSets = $this->cleanSets($variableSets, $variables);
		return $bindings;
	}

	private function bindPredicationTail(array $predications, array $variables, array $knowledgeSources, array $ruleSources)
	{
		if (empty($predications)) {

			return array($variables);

		} else {

			/** @var array $variableSets An array of sets of variable bindings. (i.e. [ [a = 2, b = 5], [a = 4, b = 7] ] */
			$variableSets = array();

			// take the first predication
			$Predication = array_shift($predications);
			$predicate = $Predication->getPredicate();
			$argumentCount = $Predication->getArgumentCount();

			// fill the variables of $Predication with the current bindings
			$BoundPredication = $this->bindPredication($Predication, $variables);

			// knowledge sources may provide bindings
			foreach ($knowledgeSources as $KnowledgeSource) {

				$ksSets = $KnowledgeSource->bind($BoundPredication);

				// for each of these bindings, continue with the rest of the predications
				$variableSets = array_merge($variableSets, $this->bindMultipleTails($predications, $variables, $ksSets, $knowledgeSources, $ruleSources));

			}

			// rule sources may provide bindings
			foreach ($ruleSources as $RuleSource) {

				// ask for the rules that are applicable here
				$rules = $RuleSource->getRulesByPredicate($predicate, $argumentCount);

				// go through each of these rules separately
				foreach ($rules as $GoalClause) {

					$gSets = $this->performGoal($GoalClause, $BoundPredication, $knowledgeSources, $ruleSources);

					// for each of these bindings, continue with the rest of the predications
					$variableSets = array_merge($variableSets, $this->bindMultipleTails($predications, $variables, $gSets, $knowledgeSources, $ruleSources));

				}
			}

			return $variableSets;
		}
	}

	private function cleanSets($variableSets, $variables)
	{
		$newSets = array();

		foreach ($variableSets as $set) {
			$newSets[] = array_intersect_key($variables, $set);
		}

		return $newSets;
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

	private function bindMultipleTails(array $predications, array $variables, array $newSets, array $knowledgeSources, array $ruleSources)
	{
		$bindings = array();

		foreach ($newSets as $newVariables) {

			// combine the bindings returned by the knowledge source with the existing bindings
			$combinedVariables = array_merge($variables, $newVariables);

			// and proceed with the rest of the predications
			$bindings = array_merge($bindings, $this->bindPredicationTail($predications, $combinedVariables, $knowledgeSources, $ruleSources));

		}

		return $bindings;
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
