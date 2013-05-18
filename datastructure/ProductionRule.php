<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class ProductionRule
{
	private $antecedent;

	private $consequents;

	public function setAntecedent($antecedent)
	{
		$this->antecedent = $antecedent;
	}

	public function setConsequents($consequents)
	{
		$this->consequents = $consequents;
	}

	public function getConsequents()
	{
		return $this->consequents;
	}

	public function getConsequentCount()
	{
		return count($this->consequents);
	}

	public function getConsequent($index)
	{
		return $this->consequents[$index];
	}

	public function __toString()
	{
		return $this->antecedent . ' => ' . implode(' ', $this->consequents);
	}

	public function createClone()
	{
		$Clone = new ProductionRule();
		$Clone->setAntecedent($this->antecedent);
		$Clone->setConsequents($this->consequents);
		return $Clone;
	}
}
