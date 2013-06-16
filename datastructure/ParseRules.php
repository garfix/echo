<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class ParseRules
{
	private $rules = array();

	/** @var array An antecedent-based index of parse rules */
	private $index = array();

	public function setRules($rules)
	{
		$this->rules = $rules;
	}

	public function getRules()
	{
		return $this->rules;
	}

	public function addRule(ParseRule $Rule)
	{
		$this->rules[] = $Rule;
		$this->indexRule($Rule);
	}

	/**
	 * Returns all parse rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[ParseRule]
	 */
	public function getRulesForAntecedent($antecedent)
	{
		if (isset($this->index[$antecedent])) {
			return $this->index[$antecedent];
		} else {
			return array();
		}
	}

	public function __toString()
	{
		return implode(' ', $this->rules);
	}

	private function indexRule(ParseRule $ParseRule)
	{
		$Production = $ParseRule->getProduction();
		$antecedent = $Production->getAntecedentCategory();
		$this->index[$antecedent][] = $ParseRule;
	}
}
