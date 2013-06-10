<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class SemanticParseException extends EchoException
{
	const SEMANTIC = 'Could not calculate semantics for the part that starts with "%s"';

	private $lexicalItems = null;
	private $lastParsedIndex = null;

	public function __construct($lexicalItems, $lastParsedIndex)
	{
		$this->lexicalItems = $lexicalItems;
		$this->lastParsedIndex = $lastParsedIndex;
		$this->message = self::SEMANTIC;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), implode(' ', array_slice($this->lexicalItems, $this->lastParsedIndex, 4)));
	}
}
