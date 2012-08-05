<?php

namespace agentecho\exception;

use \Exception;

class LexicalItemException extends EchoException
{
	const WORD_NOT_FOUND = 'Word not found: %1$s';

	private $word;

	public function setWord($word)
	{
		$this->word = $word;
	}

	public function getWord()
	{
		return $this->word;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), $this->word);
	}
}