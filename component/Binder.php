<?php

namespace agentecho\component;

use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
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
	 * @param Relation $Relation
	 * @param array $bindings
	 * @return Relation If replacements were made, a new Relation, otherwise $Relation.
	 */
	public static function bindRelationVariables(Relation $Relation, array &$bindings)
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

	public static function bindRelationsVariables(RelationList $Relations, array &$bindings)
	{
		$NewList = new RelationList();

		foreach ($Relations->getRelations() as $Relation) {
			$NewList->addRelation(self::bindRelationVariables($Relation, $bindings));
		}

		return $NewList;
	}
}
