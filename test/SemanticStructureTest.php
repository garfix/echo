<?php

namespace agentecho\test;

use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Atom;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class SemanticStructureTest extends \PHPUnit_Framework_TestCase
{
	public function testPredicationVariableNames()
	{
		$Predication = new Predication();
		$Arg1 = new Atom('ape');
		$Arg2 = new Variable('x');
		$Arg3 = new Variable('y');
		$Predication->setArguments(array($Arg1, $Arg2, $Arg3));

		$this->assertSame(array('x' => 'x', 'y' => 'y'), $Predication->getVariableNames());
	}

	public function testPredicationListVariableNames()
	{
		$Predication1 = new Predication();
		$Arg1 = new Atom('ape');
		$Arg2 = new Variable('x');
		$Arg3 = new Variable('y');
		$Predication1->setArguments(array($Arg1, $Arg2, $Arg3));

		$Predication2 = new Predication();
		$Arg1 = new Variable('y');
		$Arg2 = new Atom('ape');
		$Arg3 = new Variable('z');
		$Predication2->setArguments(array($Arg1, $Arg2, $Arg3));

		$PredicationList = new PredicationList();
		$PredicationList->setPredications(array($Predication1, $Predication2));

		$this->assertSame(array('x' => 'x', 'y' => 'y', 'z' => 'z'), $PredicationList->getVariableNames());
	}
}
