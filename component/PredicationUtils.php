<?php

namespace agentecho\component;

use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class PredicationUtils
{
	/**
	 * Returns a new variable that is not in the $variableList
	 *
	 * @param array $variableList
	 * @return string The name of a variable
	 */
	static public function createUnusedVariableName(array $variableList)
	{
		$found = false;
		$i = 1;

		// create a list of variable names
		$names = array();
		foreach ($variableList as $Variable) {
			$names[$Variable->getName()] = true;
		}

		while (!$found) {

			$varName = 's' . $i;

			if (!isset($names[$varName])) {
				$found = true;
			} else {
				$i++;
			}
		}

		return $varName;
	}
}
