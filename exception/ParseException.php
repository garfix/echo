<?php

namespace agentecho\exception;

use \Exception;

class ParseException extends EchoException
{
	private $lexicalItems = null;
	private $lastParsedIndex = null;

	public function setLexicalItems($lexicalItems, $lastParsedIndex)
	{
		$this->lexicalItems = $lexicalItems;
		$this->lastParsedIndex = $lastParsedIndex;
	}

	public function __toString()
	{
		return
			'Could not parse the part that starts with "' .
			implode(' ', array_splice($this->lexicalItems, $this->lastParsedIndex, 4)) .
			'"';
	}
}