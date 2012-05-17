<?php

namespace agentecho\exception;

use \Exception;

class ParseException extends Exception
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
		return $this->lexicalItems[$this->lastParsedIndex];

		return
			implode(' ', array_splice($this->lexicalItems, 0, $this->lastParsedIndex)) .
			' ^ ' .
			implode(' ', array_splice($this->lexicalItems, $this->lastParsedIndex));
	}
}