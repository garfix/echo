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
		$this->message = self::COULD_NOT_PARSE;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), substr($this->string, 10));
	}
}
