<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Property;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class Matcher
{
	/**
	 * Tries to match $Predication against a predication in $Relations.
	 * Returns the bindings, if successful, or false.
	 *
	 * @param Predication $Predication
	 * @param PredicationList $Relations
	 * @param array $propertyBindings
	 * @param array $variableBindings
	 * @param BindingChecker $Checker
	 * @return array|bool
	 */
	public static function matchPredicationAgainstList(Predication $Predication, PredicationList $Relations,
	                                                   array &$propertyBindings, array &$variableBindings, BindingChecker $Checker = null)
	{
		foreach ($Relations->getPredications() as $Relation) {
			if (self::matchPredicationAgainstPredication($Predication, $Relation, $propertyBindings, $variableBindings)) {

				if (!$Checker or $Checker->check($propertyBindings, $variableBindings)) {

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Tries to match $Predication against $Relation.
	 * Returns the bindings, if successful, or false.
	 *
	 * @param Predication $Predication
	 * @param Predication $Relation
	 * @param $propertyBindings
	 * @param $variableBindings
	 * @return bool
	 */
	public static function matchPredicationAgainstPredication(Predication $Predication, Predication $Relation, array &$propertyBindings, array &$variableBindings)
	{
		if ($Predication->getPredicate() != $Relation->getPredicate()) {
			return false;
		}

		$newPropertyBindings = $propertyBindings;
		$newVariableBindings = $variableBindings;

		foreach ($Predication->getArguments() as $index => $PredicationArgument) {

			$match = false;

			$RelationArgument = $Relation->getArgument($index);

			if ($PredicationArgument instanceof Atom) {
				if ($RelationArgument instanceof Atom) {
					if ($PredicationArgument == $RelationArgument) {
						// Declarative = Declarative
						$match = true;
					}
				}
			} elseif ($PredicationArgument instanceof Variable) {
				if ($RelationArgument instanceof Variable) {

					// has the variable been bound?
					if (isset($newVariableBindings[$PredicationArgument->getName()])) {
						// check if it was bound to the same variable
						if ($newVariableBindings[$PredicationArgument->getName()] == $RelationArgument) {
							$match = true;
						}
					} else {
						$newVariableBindings[$PredicationArgument->getName()] = $RelationArgument;
						$match = true;
					}

				} else {
					// presume constant or atom
					// ?e = Of, ?name = "John Milton"
					$newVariableBindings[$PredicationArgument->getName()] = $RelationArgument;
					$match = true;
				}
			} elseif ($PredicationArgument instanceof Property) {
				if ($RelationArgument instanceof Variable) {
					// S.subject = ?s
					if (isset($newPropertyBindings[(string)$PredicationArgument])) {
						if ($newPropertyBindings[(string)$PredicationArgument] == $RelationArgument) {
							$match = true;
						}
					} else {
						$newPropertyBindings[(string)$PredicationArgument] = $RelationArgument;
						$match = true;
					}

				} elseif ($RelationArgument instanceof Property) {
					if ($RelationArgument->getName() == $PredicationArgument->getName()) {
						$relationObjectName = $RelationArgument->getObject()->getName();
						$predicationObjectName = $PredicationArgument->getObject()->getName();
						if ($relationObjectName == 'this' || $relationObjectName == $predicationObjectName) {
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
