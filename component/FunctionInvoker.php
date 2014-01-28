<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
use agentecho\datastructure\FunctionApplication;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class FunctionInvoker
{
	public function applyFunctionApplication(FunctionApplication $FunctionApplication, $arguments)
	{
		return $this->apply($FunctionApplication, $arguments, 'functions');
	}

	public function applyTemplateFunctionApplication(FunctionApplication $FunctionApplication, $arguments, RelationList $Question)
	{
		return $this->apply($FunctionApplication, $arguments, 'templatefunctions', $Question);
	}

	private function apply(FunctionApplication $FunctionApplication, $arguments, $type, RelationList $Question = null)
	{
		$Function = $this->getFunction($FunctionApplication->getName(), $type);
		$formalParameters = $FunctionApplication->getArguments();
		$actualParameters = array();
		foreach ($formalParameters as $Param) {
			if ($Param instanceof Variable) {
				$actualParameters[] = $arguments[$Param->getName()];
			} elseif ($Param instanceof Constant) {
				$actualParameters[] = $Param;
			} elseif ($Param instanceof Atom) {
				$actualParameters[] = $Param;
			} elseif ($Param instanceof FunctionApplication) {
				$actualParameters[] = $this->applyFunctionApplication($Param, $arguments);
			} else {
				die('not implemented function value');
			}
		}

		if ($type == 'templatefunctions') {
			$result = $Function->invoke($actualParameters, $Question);
		} else {
			$result = $Function->invoke($actualParameters);
		}

		return $result;
	}

	private function getFunction($name, $type)
	{
		$class = 'agentecho\\' . $type . '\\' . ucfirst($name);
		return new $class;
	}
}
