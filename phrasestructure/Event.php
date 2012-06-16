<?php

namespace agentecho\phrasestructure;

class Event
{
	private $predicate = null;
	private $arguments = array();

	public function setPredicate($predicate)
	{
		$this->predicate = $predicate;
	}

	public function setArguments(array $arguments)
	{
		$this->arguments = $arguments;
	}
}