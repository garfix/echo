<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class GenerationRule
{
	/** @var ProductionRule */
	private $Production = null;

	/** @var PredicationList */
	private $Condition = null;

	/** @var AssignmentList */
	private $Assignments = null;

	/** @var PredicationList */
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
	 * @param PredicationList $Condition
	 */
	public function setCondition(PredicationList $Condition)
	{
		$this->Condition = $Condition;
	}

	/**
	 * @return PredicationList
	 */
	public function getCondition()
	{
		return $this->Condition;
	}

	/**
	 * @param PredicationList $WordSemantics
	 */
	public function setWordSemantics(PredicationList $WordSemantics)
	{
		$this->WordSemantics = $WordSemantics;
	}

	/**
	 * @return PredicationList
	 */
	public function getWordSemantics()
	{
		return $this->WordSemantics !== null ? $this->WordSemantics : new PredicationList();
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
