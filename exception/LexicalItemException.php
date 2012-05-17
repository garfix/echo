<?php

namespace agentecho\exception;

use \Exception;

class LexicalItemException extends Exception
{
	private $word;

	public function setWord($word)
	{
		$this->word = $word;
	}

	public function getWord()
	{
		return $this->word;
	}
}