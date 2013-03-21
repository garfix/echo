<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class MissingRequestFieldException extends EchoException
{
	const ERROR = 'The semantics of the sentence misses the `S.request` field';

	private $missingPredications;

	public function __construct()
	{
		$this->message = self::ERROR;
	}

	public function __toString()
	{
		return $this->getMessage();
	}
}
