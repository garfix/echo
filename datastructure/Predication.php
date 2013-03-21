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

	public function setArgument($index, $Argument)
	{
		$this->arguments[$index] = $Argument;
	}

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

	public function getArgument($index)
	{
		return $this->arguments[$index];
	}

	public function getArgumentCount()
	{
		return count($this->arguments);
	}

	/**
	 * Returns a name => name list of the names of the variables used in this predication.
	 * @return array
	 */
	public function getVariableNames()
	{
		$names = array();

		foreach ($this->getArguments() as $Argument) {
			if ($Argument instanceof Variable) {
				$name = $Argument->getName();
				$names[$name] = $name;
			}
		}

		return $names;
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

	/**
	 * Returns false if $Predication does not match this predication;
	 * and an array of mapped variable values on a match.
	 *
	 * @param Predication $Predication
	 * @return array|bool
	 */
	public function match(Predication $Predication)
	{
		if ($Predication->getPredicate() != $this->predicate) {
			return false;
		}

		$hisArguments = $Predication->getArguments();
		if (count($hisArguments) != count($this->arguments)) {
			return false;
		}

		$match = array();
		foreach ($this->arguments as $index => $Argument) {
			if ($Argument instanceof Variable) {
				$varName = $Argument->getName();
				$match[$varName] = $hisArguments[$index];
			} elseif ($Argument instanceof Constant || $Argument instanceof Atom) {
				if ($Argument != $hisArguments[$index]) {
					return false;
				}
			}
		}

		return $match;
	}
}
