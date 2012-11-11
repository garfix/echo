<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Constant
{
	private $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function __toString()
	{
		return '"' . $this->name . '"';
	}
}
