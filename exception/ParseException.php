<?php

namespace agentecho\exception;

class ParseException extends EchoException
{
	const COULD_NOT_PARSE = 'Could not parse the part that starts with "%s"';
	const NO_SEMANTICS_AT_TOP_LEVEL = 'No semantic rule was defined at sentence level.';
	const DB_MORE_THAN_ONE_RESULT = 'The database returned more than one result. Please add more semantic constraints.';

	private $lexicalItems = null;
	private $lastParsedIndex = null;

	public function __construct($messageText, $lexicalItems = null, $lastParsedIndex = null)
	{
		$this->messageText = $messageText;
		$this->lexicalItems = $lexicalItems;
		$this->lastParsedIndex = $lastParsedIndex;
		$this->buildMessage();
	}

	public function buildMessage()
	{
		$this->message =
			($this->lexicalItems !== null) ?
			sprintf($this->messageText, implode(' ', array_slice($this->lexicalItems, $this->lastParsedIndex, 4))) :
			$this->messageText;
	}
}