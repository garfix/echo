<?php

namespace agentecho\exception;

class ParseException extends EchoException
{
	const COULD_NOT_PARSE = 'Could not parse the part that starts with "%s"';
	const NO_SEMANTICS_AT_TOP_LEVEL = 'No semantic rule was defined at sentence level.';
	const DB_MORE_THAN_ONE_RESULT = 'The database returned more than one result. Please add more semantic constraints.';

	private $lexicalItems = null;
	private $lastParsedIndex = null;

	public function setLexicalItems($lexicalItems, $lastParsedIndex)
	{
		$this->lexicalItems = $lexicalItems;
		$this->lastParsedIndex = $lastParsedIndex;
	}

	public function __toString()
	{
		if ($this->lexicalItems != null) {
			return sprintf($this->getMessage(), implode(' ', array_splice($this->lexicalItems, $this->lastParsedIndex, 4)));
		} else {
			return $this->getMessage();
		}
	}
}