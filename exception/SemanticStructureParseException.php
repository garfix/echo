<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class SemanticStructureParseException extends EchoException
{
	const COULD_NOT_PARSE = 'Could not parse the part that starts with "%s"';

	public $pos;

	public function __construct($pos, $string)
	{
		$this->pos = $pos;
		$this->string = $string;
		$this->messageText = self::COULD_NOT_PARSE;
		$this->buildMessage();
	}

	public function buildMessage()
	{
		$this->message = sprintf($this->messageText, substr($this->string, $this->pos, 40));
	}
}
