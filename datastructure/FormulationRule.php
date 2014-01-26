<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class FormulationRule
{
	const TYPE_SINGLE = 'single';
	const TYPE_MULTIPLE = 'multiple';
	const TYPE_YES_NO = 'yesno';

	/** @var RelationList */
	private $Condition = null;

	/** @var RelationList */
	private $Production = null;

	/** @var string */
	private $type = null;

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
	 * @param RelationList $Production
	 */
	public function setProduction(RelationList $Production)
	{
		$this->Production = $Production;
	}

	/**
	 * @return RelationList
	 */
	public function getProduction()
	{
		return $this->Production !== null ? $this->Production : new RelationList();
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	public function typeExists($type)
	{
		return in_array($type, array(self::TYPE_SINGLE, self::TYPE_MULTIPLE, self::TYPE_YES_NO));
	}

	public function __toString()
	{
		$values = array();

		if ($this->Condition) {
			$values[] = 'conditon: ' . $this->Condition;
		}

		if ($this->type) {
			$values[] = 'type: ' . $this->type;
		}

		if ($this->Production) {
			$values[] = 'production: ' . $this->Production;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
