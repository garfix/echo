<?php

namespace agentecho\datastructure;

use agentecho\datastructure\Tree;
use agentecho\datastructure\ProductionRule;

/**
 * @author Patrick van Bergen
 */
class GenerationRule
{
	/** @var ProductionRule */
	private $Production = null;

	/** @var LabeledDAG */
	private $Condition = null;

	/** @var LabeledDAG */
	private $Features = null;

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
	 * @param LabeledDAG $Condition
	 */
	public function setCondition(Tree $Condition)
	{
		$this->Condition = $Condition;
	}

	/**
	 * @return Tree
	 */
	public function getCondition()
	{
		return $this->Condition;
	}

	/**
	 * @param LabeledDAG $Features
	 */
	public function setFeatures(LabeledDAG $Features)
	{
		$this->Features = $Features;
	}

	/**
	 * @return LabeledDAG
	 */
	public function getFeatures()
	{
		return $this->Features;
	}

	public function __toString()
	{
		$values = array();

		if ($this->Production) {
			$values[] = 'rule: ' . $this->Production;
		}

		if ($this->Condition) {
			$values[] = 'condition: ' . $this->Condition;
		}

		if ($this->Features) {
			$values[] = 'features: ' . $this->Features;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
