<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class FormulationRule
{
	/** @var RelationList */
	private $Condition = null;

	/** @var RelationList */
	private $AddList = null;

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
	 * @param RelationList $AddList
	 */
	public function setAddList(RelationList $AddList)
	{
		$this->AddList = $AddList;
	}

	/**
	 * @return RelationList
	 */
	public function getAddList()
	{
		return $this->AddList !== null ? $this->AddList : new RelationList();
	}

	public function __toString()
	{
		$values = array();

		if ($this->Condition) {
			$values[] = 'conditon: ' . $this->Condition;
		}

		if ($this->AddList) {
			$values[] = 'add: ' . $this->Production;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
