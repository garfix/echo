<?php

namespace agentecho\component;

use agentecho\datastructure\Relation;
use agentecho\datastructure\FunctionApplication;

/**
 * @author Patrick van Bergen
 */
class Aggregator
{
	/**
	 * In a aggregate(?a, ?b), place the result of ?b in ?a.
	 *
	 * @param Relation $AggregatorRelation
	 * @param array $results
	 * @return array
	 */
	public function applyAggregate(Relation $AggregatorRelation, array $results)
	{
		$Invoker = new FunctionInvoker();
		$Variable = $AggregatorRelation->getFirstArgument();
		$varName = $Variable->getName();
		$Value = $AggregatorRelation->getSecondArgument();

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

			$results[0][$varName] = $Invoker->applyFunctionApplication($Function, $arguments);
		}

		return $results;
	}
}
