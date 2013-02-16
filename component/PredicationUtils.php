<?php

namespace agentecho\component;

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
	public static function createUnusedVariableName(array $variableList)
	{
		$found = false;
		$i = 1;

		// create a list of variable names
		$names = array();
		foreach ($variableList as $Variable) {
			$names[$Variable->getName()] = true;
		}

		while (!$found) {

			$varName = 'b' . $i;

			if (!isset($names[$varName])) {
				$found = true;
			} else {
				$i++;
			}
		}

		return $varName;
	}

	/**
	 * Variable names are found in  $variableKeyNameList.
	 *
	 * @param array $variableKeyNameList
	 * @return string
	 */
	public static function createUnusedVariableName2(array $variableKeyNameList)
	{
		$found = false;
		$i = 1;

		while (!$found) {

			$varName = 's' . $i;

			if (!in_array($varName, $variableKeyNameList)) {
				$found = true;
			} else {
				$i++;
			}
		}

		return $varName;
	}
}
