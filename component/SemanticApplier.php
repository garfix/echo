<?php

namespace agentecho\component;

use agentecho\datastructure\Atom;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\LambdaExpression;
use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\Assignment;
use agentecho\datastructure\Property;
use agentecho\datastructure\SemanticNode;
use agentecho\datastructure\SemanticStructure;
use agentecho\datastructure\TermList;

/**
 * @author Patrick van Bergen
 */
class SemanticApplier
{
	/**
	 * @param $Rule
	 *
	 * Example (an Assignmentlist that can be paraphrased as):
	 * 	S.sem = WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 *	S.event = WhNP.object;
	 *	S.subject = NP.object;
	 *	S.request = WhNP.request
	 *
	 * @param array $arguments An array of [cat => SemanticStructure]
	 *
	 * Example:
	 *  NP => isa(this.object, Old)
	 *
	 * @return SemanticStructure
	 */
	public function apply($Rule, $arguments)
	{
		if ($Rule instanceof AssignmentList) {

			$assignments = $Rule->getAssignments();
			$SemanticNode = new SemanticNode();

			foreach ($assignments as $Assignment) {
				$this->applyAssignment($Assignment, $SemanticNode);
			}

			$Semantics = $this->applyAttachment($SemanticNode->attachment, $SemanticNode->bindings, $arguments);

$p = (string)$Semantics;

			return $Semantics;

		} elseif ($Rule instanceof Atom) {

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

	private function applyAssignment(Assignment $Assignment, SemanticNode $SemanticNode)
	{
		/** @var Property $Left  */
		$Left = $Assignment->getLeft();
		$Right = $Assignment->getRight();

		if ($Left->getName() == 'sem') {
			$SemanticNode->attachment = $Right;
		} else {
			$SemanticNode->bindings[] = array($Left->getName() => $Right);
		}
	}

	// $Attachment example:
	//      WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	//
	// $bindings example:
	//      'object' => NP.object
	//
	// $arguments example:
	//      'NP' => isa(this.object, Old)
	private function applyAttachment(TermList $TermList, array $bindings, array $arguments)
	{
		$predications = array();

		foreach ($TermList->getTerms() as $Term) {

			if ($Term instanceof Property) {

				if ($Term->getName() == 'sem') {

					#todo: make a (deep) clone
					$name = $Term->getObject()->getName();
					if (isset($arguments[$name])) {
						$childPredications = $arguments[$name]->getPredications();


						foreach ($childPredications as $Predication) {
							// replace child's variables by parent variables according to $bindings
							$this->replaceVariables($Predication, $bindings);
						}

						$predications = array_merge($predications, $childPredications);
					} else {
						//die("don't know this name");
					}


				} else {
					die("don't know this property");
				}

			} elseif ($Term instanceof Predication) {

				// clone the predication
				$clonedPredication = clone $Term;

				$predications[] = $clonedPredication;

			} else {
				die("don't know this term");
			}

		}

		$PredicationList = new PredicationList();
		$PredicationList->setPredications($predications);
		return $PredicationList;

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

$a = 0;
	}

	private function replaceVariables(Predication $Predication, array $bindings)
	{
		/** @var Property $Argument */
		foreach ($Predication->getArguments() as $Argument) {

			if ($Argument instanceof Property) {
				$childName = $Argument->getName();

				$found = false;

				foreach ($bindings as $binding) {

					foreach ($binding as $parentName => $TermList) {
						$terms = $TermList->getTerms();
						$Property = reset($terms);
						$name = $Property->getName();

						if ($name == $childName) {
							$Argument->setName($parentName);
							$found = true;
						}
					}
				}

				if (!$found) {
					//die('Did not find binding for ' . $childName);
				}
			}
		}

		$a = 0;
	}
}
