<?php

namespace agentecho\datastructure;

/**
 * A production rule is a rule like this
 *
 * S => NP VP
 *
 * or
 *
 * S => VP NP1 NP2
 *
 * @author Patrick van Bergen
 */
class ProductionRule
{
	private $antecedent;

	private $consequents;

	/** @var  array The categories without the numeric indexes */
	private $consequentCategories;

	public function setAntecedent($antecedent)
	{
		$this->antecedent = $antecedent;
	}

	public function getAntecedent()
	{
		return $this->antecedent;
	}

	public function setConsequents($consequents)
	{
		$this->consequents = $consequents;

		foreach ($this->consequents as $consequent) {

			preg_match('/([^0-9]+)/', $consequent, $matches);
			$this->consequentCategories[] = $matches[0];
		}
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

	public function getConsequentCategories()
	{
		return $this->consequentCategories;
	}

	public function getConsequentCategory($index)
	{
		return $this->consequentCategories[$index];
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
