<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Property
{
	private $name;
	private $object;

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setObject($object)
	{
		$this->object = $object;
	}

	public function getObject()
	{
		return $this->object;
	}

	public function __toString()
	{
		return $this->object . '.' . $this->name;
	}
}
