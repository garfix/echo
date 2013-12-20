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
	private $Condition1 = null;

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
	 * @param PredicationList $Condition1
	 */
	public function setCondition1(PredicationList $Condition1)
	{
		$this->Condition1 = $Condition1;
	}

	/**
	 * @return PredicationList
	 */
	public function getCondition1()
	{
		return $this->Condition1;
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
