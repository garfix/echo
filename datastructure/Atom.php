<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Atom extends SemanticStructure
{
	private $name = '';

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function __toString()
	{
		return $this->name;
	}

	public function createClone()
	{
		$Clone = new Atom($this->name);
		return $Clone;
	}
}
