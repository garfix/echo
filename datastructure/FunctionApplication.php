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

	public function setArgument($index, $Argument)
	{
		$this->arguments[$index] = $Argument;
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
		return (empty($this->arguments) ? false : $this->arguments[0]);
	}

	public function getSecondArgument()
	{
		return (empty($this->arguments) ? false : $this->arguments[1]);
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
		$Clone = new FunctionApplication();
		$Clone->setName($this->name);

		$arguments = array();
		foreach ($this->arguments as $Argument) {
			$arguments[] = $Argument->createClone();
		}
		$Clone->setArguments($arguments);

		return $Clone;
	}
}
