<?php

namespace agentecho\component;

/**
 * @author Patrick van Bergen
 */
class PredicationUtils
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
}
