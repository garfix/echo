<?php

namespace agentecho\phrasestructure;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Variable;
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

	/**
	 * Creates a set of conjunction relations from given entities.
	 *
	 * @param array $entities Constants, atoms, or variables, or a combination thereof.
	 * @throws \agentecho\exception\BuildException
	 * @return PredicationList
	 */
	public static function buildConjunction2(array $entities, Variable $RootVariable)
	{
		static $idGen = 0;

		if (count($entities) < 2) {
			throw new BuildException();
        }

		$Relations = new PredicationList();
		$count = count($entities);

		$RightNode = new Variable('n' . ++$idGen);
		$NameNode = $entities[$count - 1];

		$Relation = new Predication();
		$Relation->setPredicate('name');
		$Relation->setArguments(array($RightNode, $NameNode));
		$Relations->addPredication($Relation);

		for ($i = $count - 2; $i >= 0; $i--) {

			if ($i == 0) {
				$TopNode = $RootVariable;
			} else {
				$TopNode = new Variable('n' . ++$idGen);
			}

			$LeftNode = new Variable('n' . ++$idGen);
			$NameNode = $entities[$i];

			$Relation = new Predication();
			$Relation->setPredicate('name');
			$Relation->setArguments(array($LeftNode, $NameNode));
			$Relations->addPredication($Relation);

			$Relation = new Predication();
			$Relation->setPredicate('link');
			$Relation->setArguments(array(new Atom('And'), $TopNode, $LeftNode, $RightNode));
			$Relations->addPredication($Relation);

			$RightNode = $TopNode;
		}

		return $Relations;
	}
}