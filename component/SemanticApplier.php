<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\LambdaExpression;

/**
 * @author Patrick van Bergen
 */
class SemanticApplier
{
	/**
	 * @param $Rule
	 * @param array $arguments An array of [cat =>  expression)
	 */
	public function apply($Rule, $arguments)
	{
		if ($Rule instanceof Atom) {

			$cat = $Rule->getName();
			$Argument = $arguments[$cat];
			return $Argument;

		} elseif ($Rule instanceof PredicationList) {

			/** @var PredicationList $List  */
			$List = $Rule;

			foreach ($List->getPredications() as $Predication) {

				if (!$this->applyPredication($Predication, $arguments)) {
					return false;
				}

			}

		} else {
			$a = 0;
			#todo
		}
	}

	private function applyPredication(Predication $Predication, array $arguments)
	{
		$BoundPredicate = $arguments[$Predication->getPredicate()];

		$boundArguments = array();
		foreach ($Predication->getArguments() as $argument) {
			if ($argument instanceof Atom) {
				/** @var Atom $Atom  */
				$Atom = $argument;
				$boundArguments[] = $arguments[$Atom->getName()];
			}
		}

//		$this->applyLambdaExpression($BoundPredicate, $boundArguments);

$a = 0;
	}

	/**
	 * @param LambdaExpression $Predicate
	 * @param array $arguments LambdaExpression[]
	 */
	private function applyLambdaExpression(LambdaExpression $Predicate, array $arguments)
	{

	}
}
