<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class FunctionApplication
{
	/** @var string */
	private $name;

	/** @var array */
	private $arguments;

	/**
	 * @param array $arguments
	 */
	public function setArguments($arguments)
	{
		$this->arguments = $arguments;
	}

	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	public function getFirstArgument()
	{
		return (empty($this->arguments) ? false : reset($this->arguments));
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	public function __toString()
	{
		return $this->name . '(' . implode(', ',  $this->arguments) . ')';
	}

	public function createClone()
	{
		$Clone = new Predication();
		$Clone->setName($this->name);

		$arguments = array();
		foreach ($this->arguments as $Argument) {
			$arguments[] = $Argument->createClone();
		}
		$Clone->setArguments($arguments);

		return $Clone;
	}
}
