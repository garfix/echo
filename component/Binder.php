<?php

namespace agentecho\component;

use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class Binder
{
	/**
	 * Given a $Relation, replace all the properties in its arguments with the corresponding binding in $bindings.
	 *
	 * For example:
	 *
	 *  subject(S.event, S.subject)
	 *  ['S.event' => ?e]
	 *
	 * yields
	 *
	 *  subject(?e, S.subject)
	 *
	 * @param Predication $Relation
	 * @param array $bindings
	 * @return Predication If replacements were made, a new Predication, otherwise $Relation.
	 */
	public static function bindRelationVariables(Predication $Relation, array &$bindings)
	{
		$replacementsMade = false;
		$NewRelation = $Relation;

		foreach ($Relation->getArguments() as $index => $Argument) {

			if ($Argument instanceof Variable) {

				$propertyName = $Argument->getName();

				if (isset($bindings[$propertyName])) {

					if (!$replacementsMade) {
						$NewRelation = $Relation->createClone();
					}

					$NewRelation->setArgument($index, $bindings[$propertyName]);
					$replacementsMade = true;

				}
			}
		}

		return $NewRelation;
	}

	public static function bindRelationsVariables(PredicationList $Relations, array &$bindings)
	{
		$NewList = new PredicationList();

		foreach ($Relations->getPredications() as $Relation) {
			$NewList->addPredication(self::bindRelationVariables($Relation, $bindings));
		}

		return $NewList;
	}
}
