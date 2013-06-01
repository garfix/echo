<?php

namespace agentecho\datastructure;

use agentecho\datastructure\LabeledDAG;
use agentecho\datastructure\ProductionRule;
use agentecho\datastructure\AssignmentList;

/**
 * @author Patrick van Bergen
 */
class ParseRule
{
	/** @var ProductionRule */
	private $Rule = null;

	/** @var LabeledDAG */
	private $Features = null;

	/** @var AssignmentList */
	private $Semantics = null;

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

	/**
	 * @param ProductionRule $Rule
	 */
	public function setRule(ProductionRule $Rule)
	{
		$this->Rule = $Rule;
	}

	/**
	 * @return ProductionRule
	 */
	public function getRule()
	{
		return $this->Rule;
	}

	/**
	 * @param AssignmentList $Semantics
	 */
	public function setSemantics(AssignmentList $Semantics)
	{
		$this->Semantics = $Semantics;
	}

	/**
	 * @return AssignmentList
	 */
	public function getSemantics()
	{
		return $this->Semantics;
	}

	public function __toString()
	{
		$values = array();

		if ($this->Rule) {
			$values[] = 'rule: ' . $this->Rule;
		}

		if ($this->Features) {
			$values[] = 'features: ' . $this->Features;
		}

		if ($this->Semantics) {
			$values[] = 'semantics: ' . $this->Semantics;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
