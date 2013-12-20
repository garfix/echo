<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class ParseRule
{
	/** @var ProductionRule */
	private $Production = null;

	/** @var AssignmentList */
	private $Semantics = null;

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

		if ($this->Production) {
			$values[] = 'rule: ' . $this->Production;
		}

		if ($this->Semantics) {
			$values[] = 'semantics: ' . $this->Semantics;
		}

		return '[' . implode(', ', $values) . ']';
	}
}
