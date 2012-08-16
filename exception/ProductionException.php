<?php

namespace agentecho\exception;

use \Exception;

class ProductionException extends EchoException
{
	const TYPE_UNKNOWN_CONSTITUENT = 'Production exception: unknown constituent: %s';
	const TYPE_WORD_NOT_FOUND_FOR_PARTOFSPEECH = 'Production exception: could not find a word for the part-of-speech: %s';

	private $type = null;
	private $values = array();

	public function setValue($value)
	{
		$this->values = array($value);
	}

	public function setValues($values)
	{
		$this->values = $values;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), $this->values[0]);
	}
}