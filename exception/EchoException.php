<?php

namespace agentecho\exception;

/**
 * Based class for all Echo's exceptions.
 */
class EchoException extends \Exception
{
	/**
	 * Replaces this exception's message (for translation to the active language).
	 * @param $message
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}
}