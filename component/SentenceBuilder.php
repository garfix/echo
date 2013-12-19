<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Variable;
use \agentecho\exception\BuildException;

class SentenceBuilder
{
	/**
	 * Creates a set of conjunction relations from given entities.
	 *
	 * @param array $entities Constants, atoms, or variables, or a combination thereof.
	 * @param \agentecho\datastructure\Variable $RootVariable
	 * @throws \agentecho\exception\BuildException
	 * @return PredicationList
	 */
	public static function buildConjunction(array $entities, Variable $RootVariable)
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