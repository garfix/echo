<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\Property;
use agentecho\datastructure\SemanticStructure;
use agentecho\datastructure\TermList;

/**
 * @author Patrick van Bergen
 */
class SemanticApplier
{
	/**
	 * @param AssignmentList $Rule It is an assignmentlist now; it may grow out into a complete program
	 *
	 * Example (an Assignmentlist that can be paraphrased as):
	 * 	S.sem = WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 *	S.event = WhNP.object;
	 *	S.subject = NP.object;
	 *	S.request = WhNP.request
	 *
	 * @param array $childNodeSemantics An array of [cat => SemanticStructure]
	 *
	 * Example:
	 *  NP => isa(this.object, Old)
	 *
	 * @return SemanticStructure
	 */
	public function apply($Rule, $childNodeSemantics)
	{
		if ($Rule instanceof AssignmentList) {

			// A rule consists of one or more assignments
			// like: S.event = WhNP.object
			$assignments = $Rule->getAssignments();

			// look up the syntactic category
			$parentSyntacticCategory = $assignments[0]->getLeft()->getObject()->getName();

			/** @var TermList $Attachment  */
			$SemanticAttachment = null;
			/** @var array $childPropertyBindings */
			$childPropertyBindings = array();

			// extract the semantic attachment and the bindings to child semantic properties
			// into two separate variables
			foreach ($assignments as $Assignment) {

				/** @var Property $Left  */
				$Left = $Assignment->getLeft();
				/** @var TermList $Right  */
				$Right = $Assignment->getRight();

				/** @var TermList $Attachments  */
				if ($Left->getName() == 'sem') {
					// assignment of semantics (i.e. VP.sem = verb.sem)
					$SemanticAttachment = $Right;
				} else {
					// binding of child property to parent property (VP.event = verb.event)
					$childPropertyBindings[] = array($Left->getName() => $Right);
				}
			}

			// create the predication list from this semantic attachment and the child nodes
			$Semantics = $this->applyAttachment($SemanticAttachment, $childPropertyBindings, $childNodeSemantics, $parentSyntacticCategory);

			return $Semantics;
		}
	}

	/**
	 * Create a new predication list that forms the semantic attachment of the current node.
	 *
	 * $SemanticAttachment example:
	 *      WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 *
	 * $childPropertyBindings example:
	 *      ['object' => NP.object]
	 *
	 * $childNodeSemantics example:
	 *      'NP' => isa(this.object, Old)
	 *
	 * @return PredicationList
	 */
	private function applyAttachment(TermList $SemanticAttachment, array $childPropertyBindings, array $childNodeSemantics, $parentSyntacticCategory)
	{
		$predications = array();

		// each of the terms of the semantic expression needs to be instantiated with the child properties
		foreach ($SemanticAttachment->getTerms() as $Term) {

			// there are two kinds of terms: properties (like NP.sem) and predications (like isa(this.event, Live))
			if ($Term instanceof Property) {

				if ($Term->getName() == 'sem') {

					// add the child node's predications to this node's predications
					$childPredications = $this->inheritChildNodeSemantics($Term, $childNodeSemantics, $childPropertyBindings, $parentSyntacticCategory);

					$predications = array_merge($predications, $childPredications);

				} else {
					die("Only sem is allowed as property.");
				}

			} elseif ($Term instanceof Predication) {

				/** @var Predication $ClonedPredication  */
				$ClonedPredication = $Term->createClone();

				$predications[] = $ClonedPredication;

			} else {
				die("don't know this term");
			}
		}

		$PredicationList = new PredicationList();
		$PredicationList->setPredications($predications);
		return $PredicationList;
	}

	private function replaceThisBySyntacticCategory(Predication $Predication, $syntacticCategory)
	{
		foreach ($Predication->getArguments() as $Argument) {
			if ($Argument instanceof Property) {
				/** @var $Property Property */
				$Property = $Argument;
				$Object = $Property->getObject();
				if ($Object->getName() == 'this') {
					$Object->setName($syntacticCategory);
				}
			}
		}
	}

	/**
	 * @param $Term A property like noun.sem
	 * @param $childNodeSemantics
	 * @param $childPropertyBindings
	 * @return mixed
	 */
	private function inheritChildNodeSemantics(Property $Term, $childNodeSemantics, $childPropertyBindings, $parentSyntacticCategory)
	{
		$childId = $Term->getObject()->getName();

		if (isset($childNodeSemantics[$childId])) {
			$childPredications = $childNodeSemantics[$childId]->getPredications();

			$clonedPredications = array();
			foreach ($childPredications as $Predication) {

				// copy the predication of the child
				$ClonedPredication = $Predication->createClone();

				// replace 'this' in all its arguments by the name of the syntactic category
				$this->replaceThisBySyntacticCategory($ClonedPredication, $childId);

				// replace child's variables by parent variables according to $childPropertyBindings
				$this->replaceProperties($ClonedPredication, $childPropertyBindings, $parentSyntacticCategory, $childId);

				$clonedPredications[] = $ClonedPredication;
			}

			return $clonedPredications;
		} else {
			//die("don't know this name");
			return array();
		}
	}

	/**
	 * Replaces all property arguments (like NP.subject) in a child-semantics predication with the matching
	 * properties in the parent-semantics. These are given in $childPropertyBindings.
	 *
	 * @param \agentecho\datastructure\Predication $Predication A predication like 'name(NP.object, "John")'
	 * @param array $childPropertyBindings
	 * @param string $childId The child node semantics id (like NP1, or VP)
	 */
	private function replaceProperties(Predication $Predication, array $childPropertyBindings, $parentSyntacticCategory, $childId)
	{
		/** @var Property $Argument */
		foreach ($Predication->getArguments() as $Argument) {

			if ($Argument instanceof Property) {

				// this is the name of the property that needs to be replaced
				$childName = $Argument->getName();

				$found = false;

				// go through all bindings to find the one that has $childName as its name
				foreach ($childPropertyBindings as $binding) {

					foreach ($binding as $parentName => $TermList) {

						$terms = $TermList->getTerms();
						$Property = reset($terms);
						$name = $Property->getName();
						$category = $Property->getObject()->getName();

						if ($category == $childId and $name == $childName) {
							$Argument->setName($parentName);
							$found = true;

							// change the name of the object
							$Argument->getObject()->setName($parentSyntacticCategory);

							break 2;
						}
					}
				}

				if (!$found) {

					// update the name of the object
					$Argument->getObject()->setName(
						$parentSyntacticCategory . '_' . $Argument->getObject()->getName()
					);

				}
			}
		}
	}
}
