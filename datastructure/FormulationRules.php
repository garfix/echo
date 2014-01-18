<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class FormulationRules
{
	/** @var FormulationRule[] */
	private $rules = array();

	public function setRules($rules)
	{
		$this->rules = $rules;
	}

	/**
	 * @return FormulationRule[]
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * @param FormulationRule $Rule
	 */
	public function addRule(FormulationRule $Rule)
	{
		$this->rules[] = $Rule;
	}

	public function __toString()
	{
		return implode(' ', $this->rules);
	}
}
