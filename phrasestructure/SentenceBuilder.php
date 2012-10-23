<?php

namespace agentecho\phrasestructure;

use \agentecho\exception\BuildException;
use \agentecho\phrasestructure\Conjunction;

class SentenceBuilder
{
    /**
     * Builds a (nested) conjunction of an array of entities.
     *
     * @return Conjunction
     */
    public static function buildConjunction(array $entities)
    {
        if (count($entities) < 2) {
            throw new BuildException();
        }

        $Right = array_pop($entities);

        if (count($entities) > 1) {
            $Left = self::buildConjunction($entities);
        } else {
            $Left = array_shift($entities);
        }

        $Conjunction = new Conjunction();

        $Conjunction->setLeftEntity($Left);
        $Conjunction->setRightEntity($Right);

        return $Conjunction;
    }
}