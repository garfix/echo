<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class GenerationRules
{
	private $rules = array();

	public function setRules($rules)
	{
		$this->rules = $rules;
	}

	public function getRules()
	{
		return $this->rules;
	}

	public function append(GenerationRules $Rules)
	{
		$this->rules = array_merge($this->rules, $Rules->getRules());
	}

	public function __toString()
	{
		return implode(' ', $this->rules);
	}
}
