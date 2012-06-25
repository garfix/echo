<?php

namespace agentecho\datastructure;

use \agentecho\exception\BuildException;
use \agentecho\phrasestructure\Conjunction;

class SentenceBuilder
{
	/**
	 * Builds a conjunction, based on its constituent objects.
	 *
	 * @param array $objects
	 * @return array
	 * @throws \agentecho\exception\BuildException
	 */
public static function buildConjunctionOld(array $objects)
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

    /**
     * Builds a (nested) conjunction of an array of entities.
     */
    public static function buildConjunction(array $entities)
    {
        if (count($entities) < 2) {
            throw new BuildException();
        }

        $Left = array_shift($entities);

        if (count($entities) > 1) {
            $Right = self::buildConjunction($entities);
        } else {
            $Right = array_shift($entities);
        }

        $Conjunction = new Conjunction();

        $Conjunction->setLeftEntity($Left);
        $Conjunction->setRightEntity($Right);

        return $Conjunction;
    }
}