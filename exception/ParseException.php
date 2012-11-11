<?php

namespace agentecho\exception;

class ParseException extends EchoException
{
	const COULD_NOT_PARSE = 'Could not parse the part that starts with "%s"';

	private $lexicalItems = null;
	private $lastParsedIndex = null;

	public function setLexicalItems($lexicalItems, $lastParsedIndex)
	{
		$this->lexicalItems = $lexicalItems;
		$this->lastParsedIndex = $lastParsedIndex;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), implode(' ', array_splice($this->lexicalItems, $this->lastParsedIndex, 4)));
	}
}