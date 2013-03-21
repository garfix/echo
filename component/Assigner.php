<?php

namespace agentecho\component;

use agentecho\component\FunctionInvoker;

/**
 * @author Patrick van Bergen
 */
class Assigner
{
	/**
	 * In a let(?a, ?b), place the result of ?b in ?a.
	 *
	 * @param Predication $LetPredication
	 * @param array $arguments
	 */
	public function applyLet(Predication $LetPredication, array $arguments)
	{
		$Invoker = new FunctionInvoker();
		$Variable = $LetPredication->getFirstArgument();
		$varName = $Variable->getName();
		$Value = $LetPredication->getSecondArgument();

		if ($Value instanceof FunctionApplication) {
			$arguments[$varName] = $Invoker->applyFunctionApplication($Value, $arguments);
		}

		return $arguments;
	}
}
