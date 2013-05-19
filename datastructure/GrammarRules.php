<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class GrammarRules
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

	public function __toString()
	{
		return implode(' ', $this->rules);
	}
}
