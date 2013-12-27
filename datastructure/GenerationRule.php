<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class GenerationRule
{
	/** @var ProductionRule */
	private $Production = null;

	/** @var RelationList */
	private $Condition = null;

	/** @var AssignmentList */
	private $Assignments = null;

	/** @var RelationList */
	private $WordSemantics = null;

	/**
	 * @param ProductionRule $Production
	 */
	public function setProduction(ProductionRule $Production)
	{
		$this->Production = $Production;
	}

	/**
	 * @return ProductionRule
	 */
	public function getProduction()
	{
		return $this->Production;
	}

	/**
	 * @param RelationList $Condition
	 */
	public function setCondition(RelationList $Condition)
	{
		$this->Condition = $Condition;
	}

	/**
	 * @return RelationList
	 */
	public function getCondition()
	{
		return $this->Condition;
	}

	/**
	 * @param RelationList $WordSemantics
	 */
	public function setWordSemantics(RelationList $WordSemantics)
	{
		$this->WordSemantics = $WordSemantics;
	}

	/**
	 * @return RelationList
	 */
	public function getWordSemantics()
	{
		return $this->WordSemantics !== null ? $this->WordSemantics : new RelationList();
	}

	/**
	 * @param AssignmentList $Assignments
	 */
	public function setAssignments(AssignmentList $Assignments)
	{
		$this->Assignments = $Assignments;
	}

	/**
	 * @return AssignmentList
	 */
	public function getAssignments()
	{
		return $this->Assignments;
	}

	public function __toString()
	{
		$values = array();

		if ($this->Production) {
			$values[] = 'rule: ' . $this->Production;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
