<?php

namespace agentecho\component;

use agentecho\datastructure\FunctionApplication;
use agentecho\datastructure\Relation;

/**
 * @author Patrick van Bergen
 */
class Assigner
{
	/**
	 * In a let(?a, ?b), place the result of ?b in ?a.
	 *
	 * @param Relation $LetRelation
	 * @param array $arguments
	 * @return array
	 */
	public function applyLet(Relation $LetRelation, array $arguments)
	{
		$Invoker = new FunctionInvoker();
		$Variable = $LetRelation->getFirstArgument();
		$varName = $Variable->getName();
		$Value = $LetRelation->getSecondArgument();

		if ($Value instanceof FunctionApplication) {
			$arguments[$varName] = $Invoker->applyFunctionApplication($Value, $arguments);
		}

		return $arguments;
	}
}
