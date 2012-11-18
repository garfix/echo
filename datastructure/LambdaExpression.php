<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class LambdaExpression extends Term
{
	private $variable;
	private $term;

	public function setTerm($term)
	{
		$this->term = $term;
	}

	public function getTerm()
	{
		return $this->term;
	}

	public function setVariable($variable)
	{
		$this->variable = $variable;
	}

	public function getVariable()
	{
		return $this->variable;
	}

	public function __toString()
	{
		return '{' . $this->variable . ' : ' . $this->term . '}';
	}
}
