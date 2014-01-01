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
use agentecho\exception\SemanticAttachmentException;

/**
 * @author Patrick van Bergen
 */
class SemanticApplier
{
	/**
	 * Applies $Rule to $childNodeSemantics to produce a new semantic structure (a relation list)
	 *
	 * @param AssignmentList $List It is an assignmentlist now; it may grow out into a complete program
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
	public function apply(AssignmentList $List, $childNodeSemantics, $childNodeTexts = array())
	{
		// A rule consists of one or more assignments
		// like: S.event = WhNP.entity
		$assignments = $List->getAssignments();

		// save the parent's node name
		$parentNodeName = $assignments[0]->getLeft()->getObject()->getName();

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
				$childPropertyBindings[(string)$Right] = $Left;
			}
		}

		// create the relation list from this semantic attachment and the child nodes
		return $this->applyAttachment($SemanticAttachment, $childPropertyBindings, $childNodeSemantics, $childNodeTexts, $parentNodeName);
	}

	/**
	 * Create a new relation list that forms the semantic attachment of the current node.
	 *
	 * $SemanticAttachment example:
	 *      WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	 *
	 * $childPropertyBindings example (left is child property as a string, right is parent property):
	 *      ['NP.entity' => WhNP.entity]
	 *
	 * $childNodeSemantics example:
	 *      'NP' => isa(this.entity, Old)
	 *
	 * @param TermList $SemanticAttachment
	 * @param array $childPropertyBindings
	 * @param array $childNodeSemantics
	 * @param array $childNodeTexts
	 * @param $parentNodeName
	 * @throws SemanticAttachmentException
	 * @return RelationList
	 */
	private function applyAttachment(TermList $SemanticAttachment, array $childPropertyBindings, array $childNodeSemantics, array $childNodeTexts, $parentNodeName)
	{
		// create new relations from the semantic attachment
		// explode child semantics (.sem) terms of the semantic attachment
		$Relations = $this->expandChildSemantics($SemanticAttachment, $childNodeSemantics);

		// execute operations
		$this->executeOperations($Relations, $childNodeTexts);

		// replace child properties with parent properties
		$this->replaceChildPropertiesWithParentProperties($Relations, $childPropertyBindings);

		// change the parent property NBar1 into NBar, and the orphaned child AdjP into NBar_AdjP
		$this->normalizeNodeNames($Relations, $parentNodeName);

		return $Relations;
	}

	/**
	 * Creates a set of relations from $SemanticAttachment. Expands .sem terms into new relations.
	 *
	 * @param TermList $SemanticAttachment
	 * @param RelationList[] $childNodeSemantics
	 * @return RelationList
	 * @throws SemanticAttachmentException
	 */
	private function expandChildSemantics(TermList $SemanticAttachment, array $childNodeSemantics)
	{
		$Relations = new RelationList();

		foreach ($SemanticAttachment->getTerms() as $Term) {

			if ($Term instanceof Property) {

				if ($Term->getName() == 'sem') {

					$childNodeName = $Term->getObject()->getName();
					if (isset($childNodeSemantics[$childNodeName])) {

						$ChildSemantics = $childNodeSemantics[$childNodeName]->createClone();

						// replace NP.entity with NP1.entity
						// replace this.entity with NP1.entity
						$this->renameNodeNames($ChildSemantics, $childNodeName);

						$Relations->appendRelations($ChildSemantics->getRelations());
					}

				} else {
					throw new SemanticAttachmentException("Only sem is allowed as property (not: " . $Term . ')');
				}

			} elseif ($Term instanceof Relation) {

				$Relations->addRelation($Term->createClone());

			} else {
				throw new SemanticAttachmentException("Unaccepted term class: " . get_class($Term));
			}
		}

		return $Relations;
	}

	/**
	 * Replaces all nodenames `this` with $childNodeName.
	 * If $childNodeName contains an index, replaces all nodenames `NP` with `NP1`
	 *
	 * @param RelationList $Relations
	 * @param $childNodeName
	 */
	private function renameNodeNames(RelationList $Relations, $childNodeName)
	{
		preg_match('/^([a-zA-Z]+)/', $childNodeName, $matches);
		$childNodeNameProper = $matches[1];

		foreach ($Relations->getRelations() as $Relation) {
			foreach ($Relation->getArguments() as $Argument) {

				if ($Argument instanceof Property) {

					/** @var $Property Property */
					$Property = $Argument;

					$Object = $Property->getObject();
					$name = $Object->getName();

					if ($name == 'this') {

						$Object->setName($childNodeName);

					} elseif ($name == $childNodeNameProper) {

						$Object->setName($childNodeName);

					}
				}
			}
		}
	}

	/**
	 * @param RelationList $Relations
	 * @param array $childNodeTexts
	 */
	private function executeOperations(RelationList $Relations, array $childNodeTexts)
	{
		foreach ($Relations->getRelations() as $Relation) {
			foreach ($Relation->getArguments() as $i => $Argument) {

				$Relation->setArgument($i, $this->calculateArgument($Argument, $childNodeTexts));
			}
		}
	}

	/**
	 * Replaces each child property (i.e. verb.event) with its paired parent property (i.e. VP.event).
	 *
	 * @param RelationList $Relations
	 * @param array Property[] $childPropertyBindings
	 */
	private function replaceChildPropertiesWithParentProperties(RelationList $Relations, array $childPropertyBindings)
	{
		foreach ($Relations->getRelations() as $Relation) {
			foreach ($Relation->getArguments() as $i => $Argument) {

				if ($Argument instanceof Property) {

					$PropertyString = (String)$Argument;

					if (isset($childPropertyBindings[$PropertyString])) {

						$ParentProperty = $childPropertyBindings[$PropertyString];

						$Relation->setArgument($i, $ParentProperty->createClone());
					}
				}
			}
		}
	}

	/**
	 * Prepare node names for use outside this context.
	 * Change the parent property NBar1 into NBar, and the orphaned child AdjP into NBar_AdjP
	 *
	 * @param $Relations
	 * @param string $parentNodeName
	 */
	private function normalizeNodeNames(RelationList $Relations, $parentNodeName)
	{
		preg_match('/^([a-zA-Z]+)/', $parentNodeName, $matches);
		$parentNodeNameProper = $matches[1];

		foreach ($Relations->getRelations() as $Relation) {
			foreach ($Relation->getArguments() as $i => $Argument) {

				if ($Argument instanceof Property) {

					$nodeName = $Argument->getObject()->getName();
					if ($nodeName == $parentNodeName) {

						// change parent node name NBar1 into NBar
						$newName = $parentNodeNameProper;

					} else {

						// create a unique name for an orphaned child: i.e. NBar_AdjP
						$newName = $parentNodeNameProper . '_' . $nodeName;

					}

					$Argument->getObject()->setName($newName);
				}
			}
		}
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
		// example: propernoun1.text + ' '
		if ($Argument instanceof BinaryOperation) {

			$operator = $Argument->getOperator();

			// make sure all operands are reduced to constants
			$calculatedOperands = array();
			foreach ($Argument->getOperands() as $Operand) {
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
				throw new SemanticAttachmentException('Unknown operator:' . $operator);
			}

		// example: propernoun1.text
		} elseif ($Argument instanceof Property) {

			$nodeName = $Argument->getObject()->getName();

			if ($Argument->getName() == 'text') {
				if (isset($childNodeTexts[$nodeName])) {
					$NewArgument = new Constant($childNodeTexts[$nodeName]);
				} else {
					throw new OperandNotTextException((string)$Argument);
				}
			} else {

				$NewArgument = $Argument;
			}

		} else {

			// constant or variable
			$NewArgument = $Argument;

		}

		return $NewArgument;
	}
}
