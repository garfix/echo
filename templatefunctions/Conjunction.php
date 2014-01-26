<?php

namespace agentecho\templatefunctions;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Variable;
use agentecho\exception\BuildException;

class Conjunction
{
	function invoke(array $parameters, RelationList $Question)
	{
		static $idGen = 0;

		$RootVariable = $parameters[0];
		$entities = $parameters[1];

		if (count($entities) < 2) {
			throw new BuildException();
        }

		$Relations = new RelationList();
		$count = count($entities);

		$RightNode = new Variable('n' . ++$idGen);
		$NameNode = $entities[$count - 1];

		$Relation = new Relation();
		$Relation->setPredicate('name');
		$Relation->setArguments(array($RightNode, $NameNode));
		$Relations->addRelation($Relation);

		for ($i = $count - 2; $i >= 0; $i--) {

			if ($i == 0) {
				$TopNode = $RootVariable;
			} else {
				$TopNode = new Variable('n' . ++$idGen);
			}

			$LeftNode = new Variable('n' . ++$idGen);
			$NameNode = $entities[$i];

			$Relation = new Relation();
			$Relation->setPredicate('name');
			$Relation->setArguments(array($LeftNode, $NameNode));
			$Relations->addRelation($Relation);

			$Relation = new Relation();
			$Relation->setPredicate('link');
			$Relation->setArguments(array(new Atom('And'), $TopNode, $LeftNode, $RightNode));
			$Relations->addRelation($Relation);

			$RightNode = $TopNode;
		}

		return $Relations;
	}
}