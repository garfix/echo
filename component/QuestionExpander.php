<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\datastructure\GoalClause;

/**
 * @author Patrick van Bergen
 */
class QuestionExpander
{
	/**
	 * This function finds all ways into which $Question may be expanded and returns these.
	 *
	 * @param PredicationList $Question
	 * @param array $ruleSources
	 *
	 * @return array An array of PredicationLists, each an expanded version of $Question
	 */
	public function findExpandedQuestions(PredicationList $Question, array $ruleSources)
	{
		$expandedQuestions = array();

		$ExpandedQuestion = $Question->createClone();
		$expandedQuestions[] = $ExpandedQuestion;

		$this->expandQuestion($ExpandedQuestion, 0, $expandedQuestions, $ruleSources);

		return $expandedQuestions;
	}

	/**
	 * Traverses the predications of $Question and tries to expand each of them.
	 * On each expansion, it copies the $ExpandedQuestion in a new expanded question and makes it continue on its own.
	 * When a top-level $Question is completely expanded, the $ExpandedQuestion is added to the $expandedQuestions list.
	 *
	 * @param \agentecho\datastructure\PredicationList $Question
	 * @param \agentecho\datastructure\PredicationList $ExpandedQuestion
	 * @param array $expandedQuestions
	 */
	private function expandQuestion(PredicationList $ExpandedQuestion, $index, array &$expandedQuestions, array $ruleSources)
	{
		$predications = $ExpandedQuestion->getPredications();

		for ($i = $index; $i < count($predications); $i++) {

			$Predication = $predications[$i];

			$rules = $this->findRulesForPredication($Predication, $ruleSources);

			foreach ($rules as $Rule) {

				// adapt the variables of $Means to $Question
				$snippet = $this->createNewListWithAdaptedVariables($Predication, $Rule);

				// create a new expanded question
				$NewExpandedQuestion = $ExpandedQuestion->createClone();
				$expandedQuestions[] = $NewExpandedQuestion;

				// insert $Snippet in the expanded question
				$this->insertSnippet($NewExpandedQuestion, $snippet, $i);

				// fork the expanded question
				$this->expandQuestion($NewExpandedQuestion, $i, $expandedQuestions, $ruleSources);
			}
		}
	}

	private function findRulesForPredication(Predication $Predication, array $ruleSources)
	{
		$rules = array();

		foreach ($ruleSources as $RuleSource) {

			// ask for the rules that are applicable here
			$rules = array_merge($rules, $RuleSource->getRulesByPredicate($Predication->getPredicate(), $Predication->getArgumentCount()));

		}

		return $rules;
	}

	private function createNewListWithAdaptedVariables(Predication $Predication, GoalClause $Rule)
	{
		static $v = 0;

		$Goal = $Rule->getGoal();
		$Means = $Rule->getMeans();

		// map the variable bindings from the predication to the goal clause
		$goalVariables = array();
		foreach ($Goal->getArguments() as $index => $Variable) {

			$variableName = $Variable->getName();

			// fetch the argument at the same position
			$Argument = $Predication->getArgument($index);

			if ($Argument instanceof Variable) {
				$argumentName = $Argument->getName();
#				$goalVariables[$variableName] = isset($variables[$argumentName]) ? $variables[$argumentName] : null;
				$goalVariables[$variableName] = $argumentName;
			} elseif ($Argument instanceof Constant) {
				$goalVariables[$variableName] = $Argument->getName();
			} else {
				// leave atoms and properties unchanged
				$goalVariables[$variableName] = $Argument;
			}
		}

		// replace the variables in the means list
		$snippet = $Means->createClone()->getPredications();
		foreach ($snippet as $SnippetPredication) {
			foreach ($SnippetPredication->getArguments() as $Argument) {
				if ($Argument instanceof Variable) {
					$name = $Argument->getName();
					if (!isset($goalVariables[$name])) {
						$goalVariables[$name] = 'special' . ++$v;
					}
					$newName = $goalVariables[$name];
					$Argument->setName($newName);
				} else {
					die('?');
				}
			}
		}

		return $snippet;
	}

	private function insertSnippet(PredicationList $List, array $snippet, $i)
	{
		$predications = $List->getPredications();
		$newPredications = array_merge(array_slice($predications, 0, $i), $snippet, array_slice($predications, $i + 1));
		$List->setPredications($newPredications);
	}
}
