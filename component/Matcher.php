<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Property;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class Matcher
{
	/**
	 * Tries to match $Relation against a relation in $Relations.
	 * Returns the bindings, if successful, or false.
	 *
	 * @param RelationList $TestRelationList
	 * @param RelationList $Relations
	 * @param array $propertyBindings
	 * @param array $variableBindings
	 * @param BindingChecker $Checker
	 * @return array|bool
	 */
	public static function matchRelationListAgainstRelationList(RelationList $TestRelationList, RelationList $Relations,
		array &$propertyBindings, array &$variableBindings, BindingChecker $Checker = null)
	{
		$variableBindings = array();
		$match = true;

		// go through all conditions
		foreach ($TestRelationList->getRelations() as $Condition) {

			// try to match the condition against any one of the $Relations
			if (!Matcher::matchRelationAgainstList($Condition, $Relations, $propertyBindings, $variableBindings, $Checker)) {
				$match = false;
				break;
			}
		}

		return $match;
	}

	/**
	 * Tries to match $Relation against a relation in $Relations.
	 * Returns the bindings, if successful, or false.
	 *
	 * @param Relation $TestRelation
	 * @param RelationList $Relations
	 * @param array $propertyBindings
	 * @param array $variableBindings
	 * @param BindingChecker $Checker
	 * @return array|bool
	 */
	public static function matchRelationAgainstList(Relation $TestRelation, RelationList $Relations,
	                                                   array &$propertyBindings, array &$variableBindings, BindingChecker $Checker = null)
	{
		foreach ($Relations->getRelations() as $Relation) {
			if (self::matchRelationAgainstRelation($TestRelation, $Relation, $propertyBindings, $variableBindings)) {

				if (!$Checker or $Checker->check($propertyBindings, $variableBindings)) {

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Tries to match $Relation against $Relation.
	 * Returns the bindings, if successful, or false.
	 *
	 * @param Relation $TestRelation
	 * @param Relation $Relation
	 * @param array $propertyBindings
	 * @param array $variableBindings
	 * @return bool
	 */
	public static function matchRelationAgainstRelation(Relation $TestRelation, Relation $Relation, array &$propertyBindings, array &$variableBindings)
	{
		if ($TestRelation->getPredicate() != $Relation->getPredicate()) {
			return false;
		}

		if ($TestRelation->getArgumentCount() != $Relation->getArgumentCount()) {
			return false;
		}

		$newPropertyBindings = $propertyBindings;
		$newVariableBindings = $variableBindings;

		foreach ($TestRelation->getArguments() as $index => $TestRelationArgument) {

			$match = false;

			$RelationArgument = $Relation->getArgument($index);

			if ($TestRelationArgument instanceof Atom) {
				if ($RelationArgument instanceof Atom) {
					if ($TestRelationArgument == $RelationArgument) {
						// Declarative = Declarative
						$match = true;
					}
				}
			} elseif ($TestRelationArgument instanceof Variable) {
				if ($RelationArgument instanceof Variable) {

					// has the variable been bound?
					if (isset($newVariableBindings[$TestRelationArgument->getName()])) {
						// check if it was bound to the same variable
						if ($newVariableBindings[$TestRelationArgument->getName()] == $RelationArgument) {
							$match = true;
						}
					} else {
						$newVariableBindings[$TestRelationArgument->getName()] = $RelationArgument;
						$match = true;
					}

				} else {
					// presume constant or atom
					// ?e = Of, ?name = "John Milton"
					$newVariableBindings[$TestRelationArgument->getName()] = $RelationArgument;
					$match = true;
				}
			} elseif ($TestRelationArgument instanceof Property) {
				if ($RelationArgument instanceof Variable) {
					// S.subject = ?s
					if (isset($newPropertyBindings[(string)$TestRelationArgument])) {
						if ($newPropertyBindings[(string)$TestRelationArgument] == $RelationArgument) {
							$match = true;
						}
					} else {
						$newPropertyBindings[(string)$TestRelationArgument] = $RelationArgument;
						$match = true;
					}

				} elseif ($RelationArgument instanceof Property) {
					if ($RelationArgument->getName() == $TestRelationArgument->getName()) {
						$relationObjectName = $RelationArgument->getObject()->getName();
						$testRelationObjectName = $TestRelationArgument->getObject()->getName();
						if ($relationObjectName == 'this' || $relationObjectName == $testRelationObjectName) {
							// verb.event = verb.event
							// verb.event = this.event
							$match = true;
						}
					}
				}
			}

			if (!$match) {
				return false;
			}

		}

		$variableBindings = $newVariableBindings;
		$propertyBindings = $newPropertyBindings;
		return true;
	}
}
