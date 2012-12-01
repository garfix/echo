<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Predication
{
	private $predicate = null;

	private $arguments = array();

	public function setPredicate($predicate)
	{
		$this->predicate = $predicate;
	}

	public function getPredicate()
	{
		return $this->predicate;
	}

	public function setArguments(array $arguments)
	{
		$this->arguments = $arguments;
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function __toString()
	{
		return $this->predicate . '(' . implode(', ',  $this->arguments) . ')';
	}
}
