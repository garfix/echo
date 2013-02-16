<?php

namespace agentecho\component;

use agentecho\datastructure\Predication;
use agentecho\datastructure\FunctionApplication;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class FunctionInvoker
{
	/**
	 * In a let(?a, ?b), place the result of ?b in ?a.
	 *
	 * @param Predication $LetPredication
	 * @param array $arguments
	 */
	public function applyLet(Predication $LetPredication, array $arguments)
	{
		$Variable = $LetPredication->getFirstArgument();
		$varName = $Variable->getName();
		$Value = $LetPredication->getSecondArgument();

		if ($Value instanceof FunctionApplication) {
			$arguments[$varName] = $this->applyFunctionApplication($Value, $arguments);
		}

		return $arguments;
	}

	public function applyFunctionApplication(FunctionApplication $FunctionApplication, $arguments)
	{
		$Function = $this->getFunction($FunctionApplication->getName());
		$formalParameters = $FunctionApplication->getArguments();
		$actualParameters = array();
		foreach ($formalParameters as $Param) {
			if ($Param instanceof Variable) {
				$actualParameters[] = $arguments[$Param->getName()];
			} elseif ($Param instanceof FunctionApplication) {
				$actualParameters[] = $this->applyFunctionApplication($Param, $arguments);
			} else {
				die('not implemented function value');
			}
		}
		$result = $Function->invoke($actualParameters);
		return $result;
	}

	private function getFunction($name)
	{
		$class = 'agentecho\\functions\\' . ucfirst($name);
		return new $class;
	}
}
