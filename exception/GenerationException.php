<?php

namespace agentecho\exception;

use \Exception;

class GenerationException extends EchoException
{
	const TYPE_UNKNOWN_CONSTITUENT = 1;
	const TYPE_WORD_NOT_FOUND_FOR_PARTOFSPEECH = 2;

	private $type = null;
	private $values = array();

	public function setType($type)
	{
		$this->type = $type;
	}

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
		if ($this->type == self::TYPE_UNKNOWN_CONSTITUENT) {
			return sprintf('Generation exception: unknown constituent: %s', $this->values[0]);
		} elseif ($this->type == self::TYPE_WORD_NOT_FOUND_FOR_PARTOFSPEECH) {
			return sprintf('Generation exception: could not find a word for the part-of-speech: %s', $this->values[0]);
		}
	}
}