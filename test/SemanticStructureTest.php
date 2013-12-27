<?php

namespace agentecho\test;

use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Atom;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class SemanticStructureTest extends \PHPUnit_Framework_TestCase
{
	public function testRelationVariableNames()
	{
		$Relation = new Relation();
		$Arg1 = new Atom('ape');
		$Arg2 = new Variable('x');
		$Arg3 = new Variable('y');
		$Relation->setArguments(array($Arg1, $Arg2, $Arg3));

		$this->assertSame(array('x' => 'x', 'y' => 'y'), $Relation->getVariableNames());
	}

	public function testRelationListVariableNames()
	{
		$Relation1 = new Relation();
		$Arg1 = new Atom('ape');
		$Arg2 = new Variable('x');
		$Arg3 = new Variable('y');
		$Relation1->setArguments(array($Arg1, $Arg2, $Arg3));

		$Relation2 = new Relation();
		$Arg1 = new Variable('y');
		$Arg2 = new Atom('ape');
		$Arg3 = new Variable('z');
		$Relation2->setArguments(array($Arg1, $Arg2, $Arg3));

		$RelationList = new RelationList();
		$RelationList->setRelations(array($Relation1, $Relation2));

		$this->assertSame(array('x' => 'x', 'y' => 'y', 'z' => 'z'), $RelationList->getVariableNames());
	}
}
