<?php

namespace agentecho\component;

/**
 * @author Patrick van Bergen
 */
class Utils
{
	public static function calculcateRecursionLevel()
	{
		return count(debug_backtrace());
	}

	/**
	 * This function takes as input an array like
	 * array(
	 *      array('a', 'c', 'd'),
	 *      array('x', 'z'),
	 *      array('2', '4', '6')
	 * )
	 *
	 * and returns all permutations, like this:
	 *
	 * array(
	 *      array('a', 'x', '2'),
	 *      array('a', 'x', '4'),
	 *      array('a', 'x', '6'),
	 *      array('a', 'z', '2'),
	 *      array('a', 'z', '4'),
	 *      array('a', 'z', '6'),
	 *      array('c', 'x', '2'),
	 *      ...
	 * )
	 *
	 * @param array $matchingPredicationsPerPrecondition
	 * @return array
	 */
	public static function createPermutations(array $input)
	{
		// initialize the output list
		$permutations = array();

		// the algo requires at least one element
		if (empty($input)) {
			return array();
		}

		// initialize a stack with the elements of the first level
		$stack = array();
		foreach ($input[0] as $match) {
			// use unshift to create the resultset in the expected order
			array_unshift($stack, array($match));
		}

		while ($row = array_pop($stack)) {

			// which input level should be added?
			$level = count($row);

			// any results at this level?
			if (isset($input[$level])) {

				// replace our row with as many rows as can be created from the elements at this level
				$matches = $input[$level];
				foreach ($matches as $match) {
					$newRow = $row;
					$newRow[] = $match;
					array_unshift($stack, $newRow);
				}

			} else {

				// this row is done
				$permutations[] = $row;
			}
		}

		return $permutations;
	}
}
