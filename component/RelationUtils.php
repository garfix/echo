<?php

namespace agentecho\component;

use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class RelationUtils
{
	/**
	 * Variable names are found in  $variableKeyNameList.
	 *
	 * @param array $variableKeyNameList
	 * @return string
	 */
	public static function createUnusedVariableName(array $variableKeyNameList)
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

	/**
	 * Creates a unique variable, while considering existing variable names.
	 *
	 * @param array $variableKeyNameList
	 * @return Variable
	 */
	public static function createUnusedVariable(array $variableKeyNameList)
	{
		return new Variable(self::createUnusedVariableName($variableKeyNameList));
	}
}
