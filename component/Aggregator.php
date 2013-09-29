<?php

namespace agentecho\component;

use agentecho\datastructure\Predication;
use agentecho\datastructure\FunctionApplication;

/**
 * @author Patrick van Bergen
 */
class Aggregator
{
	/**
	 * In a let(?a, ?b), place the result of ?b in ?a.
	 *
	 * @param Predication $LetPredication
	 * @param array $arguments
	 */
	public function applyAggregate(Predication $AggregatorPredication, array $results)
	{
		$Invoker = new FunctionInvoker();
		$Variable = $AggregatorPredication->getFirstArgument();
		$varName = $Variable->getName();
		$Value = $AggregatorPredication->getSecondArgument();

		if ($Value instanceof FunctionApplication) {

			$Function = $Value;
			$FunctionArgument = $Function->getFirstArgument();
			$argumentName = $FunctionArgument->getName();

			$select = array();
			foreach ($results as $result) {
				$select[] = $result[$argumentName];
			}

			$arguments = array(
				$argumentName => $select
			);
			$arguments[$varName] = $Invoker->applyFunctionApplication($Function, $arguments);
		}

		return $arguments;
	}
}
