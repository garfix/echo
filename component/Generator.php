<?php

namespace agentecho\component;

use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\GenerationRule;
use agentecho\datastructure\PredicationList;
use agentecho\exception\MissingSentenceRelationException;
use agentecho\exception\NoRuleFoundForAntecedent;
use agentecho\grammar\Grammar;

/**
 * @author Patrick van Bergen
 */
class Generator
{
	/**
	 * Generates a surface representation (a sentence) of a given list of $Relations and a $Grammar.
	 *
	 * @param Grammar $Grammar
	 * @param PredicationList $Relations
	 * @return string
	 * @throws MissingSentenceRelationException
	 */
	public function generate(Grammar $Grammar, PredicationList $Relations)
	{
		// start generating for the event that is marked as the main clause of a sentence
		$Sentence = $Relations->getPredicationByPredicate('sentence');
		if (!$Sentence) {
			throw new MissingSentenceRelationException();
		}

		// fetch the event of the main clause
		$SentenceEvent = $Sentence->getArgument(0);

		// in the next rule that fires, replace S.event with $SentenceEvent, in the condition
		$propertyBindings = array('S.event' => $SentenceEvent);

		// generate all lexical items
		$lexicalItems = $this->generateNode($Grammar, 'S', $Relations, $propertyBindings);

		return implode(' ', $lexicalItems);
	}

	/**
	 * Generates a partial surface representation for a single node.
	 *
	 * @param Grammar $Grammar
	 * @param string $antecedent
	 * @param PredicationList $Relations
	 * @param array $propertyBindings An array of bindings like S.event = ?e
	 * @return array A partial surface representation of a sentence, as an array
	 * @throws NoRuleFoundForAntecedent
	 */
	private function generateNode(Grammar $Grammar, $antecedent, PredicationList $Relations, array &$propertyBindings)
	{
		// find the first rule that matches
		$variableBindings = array();
		$Rule = $this->findMatchingRule($Grammar, $antecedent, $Relations, $propertyBindings, $variableBindings);

		$text = [];

		if ($this->isWordNode($Rule)) {

			$text[] = $this->findWord($Grammar, $Rule->getCondition1(), $variableBindings);

		} else {

			// assign new node properties
			// NP.entity = S.subject

			// go through each of the consequents
			foreach ($Rule->getProduction()->getConsequents() as $consequent) {

				$childPropertyBindings = $this->createChildProperyBindings($consequent, $propertyBindings, $Rule->getAssignments());

				$text = array_merge($text, $this->generateNode($Grammar, $consequent, $Relations, $childPropertyBindings));

			}

		}

		return $text;
	}

	private function createChildProperyBindings($node, array $propertyBindings, AssignmentList $AssignmentList)
	{
		$bindings = array();

		foreach ($AssignmentList->getAssignments() as $Assignment) {
			$left = (string)$Assignment->getLeft();
			if ($Assignment->getLeft()->getObject()->getName() != $node) {
				continue;
			}

			$right = (string)$Assignment->getRight();

			if (isset($propertyBindings[$right])) {
				$bindings[$left] = $propertyBindings[$right];
			}
		}

		return $bindings;
	}

	/**
	 * @param Grammar $Grammar
	 * @param PredicationList $Condition
	 * @param array $variableBindings
	 * @return array An array of [word, partOfSpeech], or false
	 */
	private function findWord(Grammar $Grammar, PredicationList $Condition, array $variableBindings)
	{
		$Relations = Binder::bindRelationsVariables($Condition, $variableBindings);
		$result = $Grammar->getWordForSemantics($Relations);
		return $result;
	}

	private function isWordNode(GenerationRule $Rule)
	{
		$Production = $Rule->getProduction();
		return ($Production->getConsequentCount() == 1) and ($Production->getConsequent(0) == 'word');
	}

	/**
	 * Finds a rule that has a given antecedent and whose conditions match $Relations,
	 * when the properties of the conditions have been replaced by any available $propertyBindings.
	 *
	 * @param Grammar $Grammar
	 * @param $antecedent
	 * @param PredicationList $Relations
	 * @param array $propertyBindings
	 * @throws NoRuleFoundForAntecedent
	 * @return GenerationRule
	 */
	private function findMatchingRule(Grammar $Grammar, $antecedent, PredicationList $Relations, array &$propertyBindings, array &$variableBindings)
	{
		// find all rules that match the $antecedent
		$rules = $Grammar->getGenerationRulesForAntecedent($antecedent);
		if (empty($rules)) {
			throw new NoRuleFoundForAntecedent($antecedent);
		}

		// go through all rules
		foreach ($rules as $GenerationRule) {

			$Conditions = $GenerationRule->getCondition1();
			if ($Conditions !== null) {

				$match = true;

				// go through all conditions
				foreach ($Conditions->getPredications() as $Condition) {

					// bind the condition to the active property bindings
					$BoundCondition = Binder::bindRelationProperties($Condition, $propertyBindings);

					// try to match the condition against any one of the $Relations
					if (!Matcher::matchPredicationAgainstList($BoundCondition, $Relations, $propertyBindings, $variableBindings)) {
						$match = false;
						break;
					}
				}

				if ($match) {

					return $GenerationRule;
				}
			}
		}

		throw new NoRuleFoundForAntecedent($antecedent);
	}
}
