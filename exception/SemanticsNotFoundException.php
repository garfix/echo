<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class SemanticsNotFoundException extends EchoException
{
	const SEMANTIC = 'No semantic attachment was defined for "%s" in the lexicon';

	private $lexicalItem = null;

	public function __construct($lexicalItem)
	{
		$this->lexicalItem = $lexicalItem;
		$this->message = self::SEMANTIC;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), $this->lexicalItem);
	}
}
