<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Variable
{
	private $name;
	private $value = null;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function __toString()
	{
		return '?' . $this->name;
	}

	public function createClone()
	{
		$Clone = new Variable($this->getName());
		if (is_object($this->value)) {
			$Clone->setValue($this->value->createClone());
		} else {
			$Clone->setValue($this->value);
		}
		return $Clone;
	}
}
