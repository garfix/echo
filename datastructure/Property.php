<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Property extends Term
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

	public function createClone()
	{
		$Clone = new Property();
		$Clone->setName($this->getName());
		$Clone->setObject($this->getObject()->createClone());
		return $Clone;
	}
}
