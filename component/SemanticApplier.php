<?php

namespace agentecho\component;

use agentecho\datastructure\BinaryOperation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Relation;
use agentecho\datastructure\AssignmentList;
use agentecho\datastructure\Property;
use agentecho\datastructure\SemanticStructure;
use agentecho\datastructure\TermList;
use agentecho\datastructure\Constant;
use agentecho\exception\OperandNotAcceptedException;
use agentecho\exception\OperandNotTextException;

/**
 * @author Patrick van Bergen
 */
class SemanticApplier
{
	/**
	 * Applies $Rule to $childNodeSemantics to produce a new semantic structure (a relation list)
	 *
	 * @param AssignmentList $Rule It is an assignmentlist now; it may grow out into a complete program
	 *
	 * Example (an Assignmentlist that can be paraphrased as):
	 * 	S.sem = WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 *	S.event = WhNP.entity;
	 *	S.subject = NP.entity;
	 *	S.request = WhNP.request
	 *
	 * @param array $childNodeSemantics An array of [cat => SemanticStructure]
	 * @param array $childNodeTexts An array of [cat => sentence text associated with these nodes]
	 *
	 * Example:
	 *  NP => isa(this.entity, Old)
	 *
	 * @return SemanticStructure A relation list
	 */
	public function apply($Rule, $childNodeSemantics, $childNodeTexts = array())
	{
		if ($Rule instanceof AssignmentList) {

			// A rule consists of one or more assignments
			// like: S.event = WhNP.entity
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

			// create the relation list from this semantic attachment and the child nodes
			$Semantics = $this->applyAttachment($SemanticAttachment, $childPropertyBindings, $childNodeSemantics, $childNodeTexts, $parentSyntacticCategory);

			return $Semantics;
		}
	}

	/**
	 * Create a new relation list that forms the semantic attachment of the current node.
	 *
	 * $SemanticAttachment example:
	 *      WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 *
	 * $childPropertyBindings example:
	 *      ['entity' => NP.entity]
	 *
	 * $childNodeSemantics example:
	 *      'NP' => isa(this.entity, Old)
	 *
	 * @return RelationList
	 */
	private function applyAttachment(TermList $SemanticAttachment, array $childPropertyBindings, array $childNodeSemantics, array $childNodeTexts, $parentSyntacticCategory)
	{
		$relations = array();

		// each of the terms of the semantic expression needs to be instantiated with the child properties
		foreach ($SemanticAttachment->getTerms() as $Term) {

			// there are two kinds of terms: properties (like NP.sem) and relations (like isa(this.event, Live))
			if ($Term instanceof Property) {

				if ($Term->getName() == 'sem') {

					// add the child node's relations to this node's relations
					$childRelations = $this->inheritChildNodeSemantics($Term, $childNodeSemantics, $childPropertyBindings, $parentSyntacticCategory);

					$relations = array_merge($relations, $childRelations);

				} else {
					die("Only sem is allowed as property.");
				}

			} elseif ($Term instanceof Relation) {

				$ClonedRelation = $this->calculateRelationArguments($Term, $childNodeTexts);

				$relations[] = $ClonedRelation;

			} else {
				die("don't know this term");
			}
		}

		$RelationList = new RelationList();
		$RelationList->setRelations($relations);
		return $RelationList;
	}

	private function replaceThisBySyntacticCategory(Relation $Relation, $syntacticCategory)
	{
		foreach ($Relation->getArguments() as $Argument) {
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
	 * Given a semantic attachment like
	 *    S.sem = WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 * this function processes WhNP.sem, auxBe.sem, and NP.sem as $Term
	 * and returns the semantics of the child node (i.e. WnNP, auxBe, or NP)
	 *
	 * $Term example:
	 *      WhNP.sem
	 *
	 * $childPropertyBindings example:
	 *      ['entity' => NP.entity]
	 *
	 * $childNodeSemantics example:
	 *      'NP' => isa(this.entity, Old)
	 *
	 * @return array
	 */
	private function inheritChildNodeSemantics(Property $Term, array $childNodeSemantics, array $childPropertyBindings, $parentSyntacticCategory)
	{
		// take the category of the term (i.e. NP or NP1)
		$childId = $Term->getObject()->getName();

		// look up the semantics of the child node, copy it, and replace its properties by the properties of the current node
		if (isset($childNodeSemantics[$childId])) {
			$childRelations = $childNodeSemantics[$childId]->getRelations();

			$clonedRelations = array();
			foreach ($childRelations as $Relation) {

				// copy the relation of the child
				$ClonedRelation = $Relation->createClone();

				// replace 'this' in all its arguments by the name of the syntactic category
				$this->replaceThisBySyntacticCategory($ClonedRelation, $childId);

				// replace child's variables by parent variables according to $childPropertyBindings
				$this->replaceProperties($ClonedRelation, $childPropertyBindings, $parentSyntacticCategory, $childId);

				$clonedRelations[] = $ClonedRelation;
			}

			return $clonedRelations;
		} else {
			//die("don't know this name");
			return array();
		}
	}

	/**
	 * Replaces all property arguments (like NP.subject) in a child-semantics relation with the matching
	 * properties in the parent-semantics. These are given in $childPropertyBindings.
	 *
	 * @param \agentecho\datastructure\Relation $Relation A relation like 'name(NP.entity, "John")'
	 * @param array $childPropertyBindings
	 * @param string $childId The child node semantics id (like NP1, or VP)
	 */
	private function replaceProperties(Relation $Relation, array $childPropertyBindings, $parentSyntacticCategory, $childId)
	{
		/** @var Property $Argument */
		foreach ($Relation->getArguments() as $Argument) {

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

	/*
	 * For relations like
	 *
	 *     name(this.entity, propernoun1.text + ' ' + propernoun2.text)
	 *
	 * replace the argument
	 *
	 *     propernoun1.text + ' ' + propernoun2.text
	 *
	 * with the constant
	 *
	 *     "Lord Byron"
	 *
	 * @return Relation
	 */
	private function calculateRelationArguments(Relation $Relation, array $childNodeTexts)
	{
		/** @var Relation $NewRelation  */
		$NewRelation = $Relation->createClone();

		foreach ($NewRelation->getArguments() as $index => $Argument) {

			$NewRelation->setArgument($index, $this->calculateArgument($Argument, $childNodeTexts));

		}

		return $NewRelation;
	}

	/*
	 * For an argument like
	 *
	 *     propernoun1.text + ' ' + propernoun2.text
	 *
	 * return the constant
	 *
	 *     "Lord Byron"
	 *
	 * otherwise, return the original argument.
	 *
	 * @return Argument
	 */
	private function calculateArgument($Argument, array $childNodeTexts)
	{
		if ($Argument instanceof BinaryOperation) {

			$operator = $Argument->getOperator();
			$operands = $Argument->getOperands();

			$calculatedOperands = array();
			foreach ($operands as $Operand) {
				$calculatedOperands[] = $this->calculateArgument($Operand, $childNodeTexts);
			}

			if ($operator == '+') {

				$scalarOperands = array();

				foreach ($calculatedOperands as $Operand) {

					if ($Operand instanceof Constant) {
						$scalarOperands[] = $Operand->getName();
					} else {
						throw new OperandNotAcceptedException((string)$Operand);
					}
				}

				$NewArgument = new Constant(implode('', $scalarOperands));

			} else {
				die('Unknown operator:' . $operator);
			}

		} elseif ($Argument instanceof Property) {

			$category = $Argument->getObject()->getName();
			$propertyName = $Argument->getName();

			if ($propertyName == 'text') {
				if (isset($childNodeTexts[$category])) {
					$NewArgument = new Constant($childNodeTexts[$category]);
				} else {
					throw new OperandNotTextException((string)$Argument);
				}
			} else {
				$NewArgument = $Argument;
			}

		} else {
			$NewArgument = $Argument;
		}

		return $NewArgument;
	}
}
