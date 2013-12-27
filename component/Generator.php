<?php

namespace agentecho\component;

use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\Atom;
use agentecho\datastructure\GenerationRule;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Property;
use agentecho\exception\MissingSentenceRelationException;
use agentecho\exception\NoLexicalEntryFoundForSemantics;
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
	 * @param RelationList $Relations
	 * @return string
	 * @throws MissingSentenceRelationException
	 */
	public function generate(Grammar $Grammar, RelationList $Relations)
	{
		// start generating for the event that is marked as the main clause of a sentence
		$Sentence = $Relations->getRelationByPredicate('sentence');
		if (!$Sentence) {
			throw new MissingSentenceRelationException();
		}

		// fetch the variable and property of the main clause
		$SentenceVariable = $Sentence->getArgument(0);

		/** @var Property $SentenceProperty */
		$SentenceProperty = new Property();
		$SentenceProperty->setName('event');
		$SentenceProperty->setObject(new Atom('S'));

		// in the next rule that fires, replace S.event with $SentenceEvent, in the condition
		$propertyBindings = array((string)$SentenceProperty => $SentenceVariable);

		// keep track of all the rules that have been applied
		$RulesApplied = new RulesApplied();

		// generate all lexical items
		$lexicalItems = $this->generateNode($Grammar, $SentenceProperty->getObject()->getName(), $Relations, $propertyBindings, $RulesApplied);

		// combine the lexical items into surface text
		$text = $this->createSurfaceText($Grammar, $lexicalItems);

		return $text;
	}

	/**
	 * Generates a partial surface representation for a single node.
	 *
	 * @param Grammar $Grammar
	 * @param string $antecedent
	 * @param RelationList $Relations
	 * @param array $parentPropertyBindings
	 * @param RulesApplied $RulesApplied
	 * @throws \agentecho\exception\NoLexicalEntryFoundForSemantics
	 * @return array A partial surface representation of a sentence, as an array
	 */
	private function generateNode(Grammar $Grammar, $antecedent, RelationList $Relations, array $parentPropertyBindings, RulesApplied $RulesApplied)
	{
		// find the first rule that matches,
		// and bind its properties and variables
		/** @var GenerationRule $Rule */
		list($Rule, $thisPropertyBindings, $thisVariableBindings) = $this->findMatchingRule($Grammar, $antecedent, $Relations, $parentPropertyBindings, $RulesApplied);

		$lexicalItems = [];

		if ($this->isWordNode($Rule)) {

			$lexicalItem = $this->findWord($Grammar, $antecedent, $Rule->getWordSemantics(), $thisVariableBindings);
			if ($lexicalItem === false) {
				$Relations = Binder::bindRelationsVariables($Rule->getWordSemantics(), $thisVariableBindings);
				throw new NoLexicalEntryFoundForSemantics($antecedent, (string)$Relations);
			}
			$lexicalItems[] = $lexicalItem;

		} else {

			$Production = $Rule->getProduction();

			// go through each of the consequents
			foreach ($Production->getConsequents() as $i => $consequent) {

				// assign new node properties (for example: NP.entity = S.subject)
				$childPropertyBindings = $this->createChildProperyBindings($consequent, $thisPropertyBindings, $Rule->getAssignments());

				// strip NP from NP1
				$consequentName = $Production->getConsequentCategory($i);

				// replace NP1 by NP
				$strippedPropertyBindings = array();
				foreach ($childPropertyBindings as $key => $value) {
					$newKey = str_replace($consequent . '.', $consequentName . '.', $key);
					$strippedPropertyBindings[$newKey] = $value;
				}

				$lexicalItems = array_merge($lexicalItems, $this->generateNode($Grammar, $consequentName, $Relations, $strippedPropertyBindings, $RulesApplied));
			}

		}

		return $lexicalItems;
	}

	/**
	 * Create a new set of bindings for a child node,
	 * by assigning parent bindings via a list of assignments.
	 *
	 * @param $node
	 * @param array $propertyBindings
	 * @param AssignmentList $AssignmentList
	 * @return array
	 */
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
	 * @param RelationList $Condition
	 * @param array $variableBindings
	 * @return array An array of [word, partOfSpeech], or false
	 */
	private function findWord(Grammar $Grammar, $partOfSpeech, RelationList $Condition, array $variableBindings)
	{
		$Relations = Binder::bindRelationsVariables($Condition, $variableBindings);
		$result = $Grammar->getWordForSemantics($partOfSpeech, $Relations);
		return $result;
	}

	/**
	 * Is this a rule for a word?
	 * I.e. verb => word
	 *
	 * @param GenerationRule $Rule
	 * @return bool
	 */
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
	 * @param RelationList $Relations
	 * @param array $propertyBindings
	 * @param array $variableBindings
	 * @throws NoRuleFoundForAntecedent
	 * @return GenerationRule
	 */
	private function findMatchingRule(Grammar $Grammar, $antecedent, RelationList $Relations, array $parentPropertyBindings, RulesApplied $RulesApplied)
	{
		// find all rules that match the $antecedent
		$rules = $Grammar->getGenerationRulesForAntecedent($antecedent);
		if (empty($rules)) {
			throw new NoRuleFoundForAntecedent($antecedent);
		}

		// go through all rules
		foreach ($rules as $GenerationRule) {

			$Conditions = $GenerationRule->getcondition();
			if ($Conditions !== null) {

				$propertyBindings = $parentPropertyBindings;
				$variableBindings = array();
				$match = true;
				$conditionCount = count($Conditions->getRelations());

				$RulesApplied->setGenerationRule($GenerationRule);

				// go through all conditions
				foreach ($Conditions->getRelations() as $index => $Condition) {

					// try to match the condition against any one of the $Relations
					$Checker = ($index == $conditionCount - 1) ? $RulesApplied : null;
					if (!Matcher::matchRelationAgainstList($Condition, $Relations, $propertyBindings, $variableBindings, $Checker)) {
						$match = false;
						break;
					}
				}

				if ($match) {

					// check if this combination of rule and bindings was used before for this sentence
					// this is not allowed, because it is our check against infinite recursion
					if ($conditionCount != 0 or $RulesApplied->check($propertyBindings, $variableBindings)) {

						// keep track of all rule / bindings combinations, to avoid infinite recursion
						$RulesApplied->addAppliedRule($GenerationRule, $propertyBindings, $variableBindings);

						return array($GenerationRule, $propertyBindings, $variableBindings);
					}
				}

			} else {
				return array($GenerationRule, $parentPropertyBindings, array());
			}
		}

		throw new NoRuleFoundForAntecedent($antecedent);
	}

	/**
	 * Creates a surface text string from a set of lexical entries.
	 *
	 * @param Grammar $Grammar
	 * @param array $lexicalItems An array of [word, partOfSpeech]
	 * @return string
	 */
	private function createSurfaceText(Grammar $Grammar, $lexicalItems)
	{
		if (empty($lexicalItems)) {
			return '';
		}

		// the first word should be capitalized
		$text = ucfirst($lexicalItems[0][0]);

		// add all words and precede each one with a space,
		// except the first word, and comma's
		for ($i = 1; $i < count($lexicalItems); $i++) {

			list($word, $partOfSpeech) = $lexicalItems[$i];

			$space = $word != ',';

			if ($space) {
				$text .= ' ';
			}

			$text .= $word;
		}

		$text .= '.';

        return $text;
	}
}
