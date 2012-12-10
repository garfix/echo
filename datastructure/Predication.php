<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Predication extends Term
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

	public function createClone()
	{
		$Clone = new Predication();
		$Clone->setPredicate($this->predicate);

		$arguments = array();
		foreach ($this->arguments as $Argument) {
			$arguments[] = $Argument->createClone();
		}
		$Clone->setArguments($arguments);

		return $Clone;
	}
}
