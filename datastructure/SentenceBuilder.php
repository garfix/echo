<?php

namespace agentecho\datastructure;

use agentecho\exception\BuildException;

class SentenceBuilder
{
	/**
	 * Builds a conjunction, based on its constituent objects.
	 *
	 * @param array $objects
	 * @return array
	 * @throws \agentecho\exception\BuildException
	 */
	public static function buildConjunction(array $objects)
	{
		if (count($objects) < 2) {
			throw new BuildException();
		}

		$left = array_shift($objects);

		if (count($objects) > 1) {
			$right = self::buildConjunction($objects);
		} else {
			$right = array_shift($objects);
		}

		$conjunction = array(
			'type' => 'conjunction',
			'left' => $left,
			'right' => $right
		);

		return $conjunction;
	}
}